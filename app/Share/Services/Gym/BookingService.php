<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\NotificationType;
use App\Share\Enums\PackageType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use Illuminate\Support\Facades\DB;

/**
 * Đặt lịch gói thường. Đặt lịch chỉ giữ buổi; buổi bị trừ sau giờ học, kể cả khi học viên vắng.
 */
class BookingService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function book(User $member, int $userPackageId, ClassSchedule $schedule): Booking
    {
        return DB::transaction(function () use ($member, $userPackageId, $schedule) {
            /** @var ClassSchedule $lockedSchedule */
            $lockedSchedule = ClassSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            /** @var UserPackage $package */
            $package = UserPackage::query()
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->findOrFail($userPackageId);

            $this->assertBookable($member, $package, $lockedSchedule);

            return Booking::query()->create([
                'user_id' => $member->id,
                'user_package_id' => $package->id,
                'class_schedule_id' => $lockedSchedule->id,
                'status' => BookingStatus::Confirmed,
            ]);
        });
    }

    public function cancelByMember(Booking $booking, User $member): Booking
    {
        if (! $booking->status->is(BookingStatus::Confirmed)) {
            throw new GymException(__('messages.gym_booking_already_cancelled'));
        }

        if (! $this->canMemberCancel($booking->classSchedule)) {
            throw new GymException(__('messages.gym_booking_cancel_locked', ['hours' => config('gym.cancel_lock_hours')]));
        }

        return $this->markCancelled($booking, $member);
    }

    /**
     * Nhân viên hủy không trừ buổi trong mọi trường hợp, trừ khi buổi đã được trừ.
     */
    public function cancelByStaff(Booking $booking, User $staff): Booking
    {
        if (! $booking->status->is(BookingStatus::Confirmed)) {
            throw new GymException(__('messages.gym_booking_already_cancelled'));
        }

        if ($booking->credit_deducted_at !== null) {
            throw new GymException(__('messages.gym_booking_already_deducted'));
        }

        $this->markCancelled($booking, $staff);
        $this->notifyCancelled($booking->user_id, $booking->classSchedule);

        return $booking;
    }

    /**
     * Hủy cả lớp: mọi booking chưa trừ buổi bị hủy, không mất buổi.
     */
    public function cancelSchedule(ClassSchedule $schedule, User $staff): ClassSchedule
    {
        return DB::transaction(function () use ($schedule, $staff) {
            $schedule->update(['status' => ClassScheduleStatus::Cancelled]);

            $bookings = $schedule->confirmedBookings()->whereNull('credit_deducted_at');
            $memberIds = (clone $bookings)->pluck('user_id');

            $bookings->update([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $staff->id,
            ]);

            $memberIds->each(fn (int $memberId) => $this->notifyCancelled($memberId, $schedule));

            return $schedule;
        });
    }

    /**
     * Xử lý booking đã qua giờ học. Chạy lại an toàn nhờ `credit_deducted_at`.
     *
     * @return int Số booking đã xử lý
     */
    public function deductFinishedBookings(): int
    {
        $bookingIds = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('credit_deducted_at')
            ->whereHas('classSchedule', fn ($query) => $query
                ->where('status', ClassScheduleStatus::Active)
                ->where('end_time', '<=', now()))
            ->pluck('id');

        $processed = 0;

        foreach ($bookingIds as $bookingId) {
            DB::transaction(function () use ($bookingId, &$processed) {
                /** @var Booking|null $booking */
                $booking = Booking::query()
                    ->whereNull('credit_deducted_at')
                    ->where('status', BookingStatus::Confirmed)
                    ->lockForUpdate()
                    ->find($bookingId);

                if (! $booking) {
                    return;
                }

                /** @var UserPackage $package */
                $package = UserPackage::query()->lockForUpdate()->findOrFail($booking->user_package_id);

                if ($package->remaining_credits !== null && $package->remaining_credits > 0) {
                    $package->update(['remaining_credits' => $package->remaining_credits - 1]);
                }

                $booking->update(['credit_deducted_at' => now()]);
                $processed++;
            });
        }

        return $processed;
    }

    public function canMemberCancel(ClassSchedule $schedule): bool
    {
        return now()->addHours((int) config('gym.cancel_lock_hours'))->lte($schedule->start_time);
    }

    public function isInOffPeakWindow(ClassSchedule $schedule): bool
    {
        $startTime = $schedule->start_time->format('H:i');

        return $startTime <= config('gym.off_peak_morning_end')
            || $startTime >= config('gym.off_peak_evening_start');
    }

    private function notifyCancelled(int $memberId, ClassSchedule $schedule): void
    {
        $this->notificationService->send($memberId, NotificationType::Schedule, __('messages.gym_notify_booking_cancelled', [
            'name' => $schedule->fitnessClass->name,
            'time' => $schedule->start_time->format('H:i d/m/Y'),
        ]));
    }

    private function markCancelled(Booking $booking, User $by): Booking
    {
        $booking->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $by->id,
        ]);

        return $booking;
    }

    private function assertBookable(User $member, UserPackage $package, ClassSchedule $schedule): void
    {
        if ($package->package_type->in([PackageType::PrivatePack, PackageType::PrivateGroup])) {
            throw new GymException(__('messages.gym_booking_private_package'));
        }

        if (! $schedule->status->is(ClassScheduleStatus::Active) || $schedule->start_time->isPast()) {
            throw new GymException(__('messages.gym_booking_schedule_unavailable'));
        }

        if ($package->end_date->endOfDay()->lt($schedule->start_time)) {
            throw new GymException(__('messages.gym_booking_package_expired'));
        }

        if ($package->is_off_peak && ! $this->isInOffPeakWindow($schedule)) {
            throw new GymException(__('messages.gym_booking_off_peak_only'));
        }

        $alreadyBooked = $schedule->confirmedBookings()->where('user_id', $member->id)->exists();

        if ($alreadyBooked) {
            throw new GymException(__('messages.gym_booking_duplicate'));
        }

        if ($schedule->confirmedBookings()->count() >= $schedule->max_slot) {
            throw new GymException(__('messages.gym_booking_full'));
        }

        if ($package->remaining_credits !== null && $this->availableCredits($package) <= 0) {
            throw new GymException(__('messages.gym_booking_no_credits'));
        }
    }

    /**
     * Số buổi còn đặt được: buổi còn lại trừ các buổi đã đặt nhưng chưa bị trừ.
     */
    private function availableCredits(UserPackage $package): int
    {
        $held = Booking::query()
            ->where('user_package_id', $package->id)
            ->where('status', BookingStatus::Confirmed)
            ->whereNull('credit_deducted_at')
            ->count();

        return $package->remaining_credits - $held;
    }
}
