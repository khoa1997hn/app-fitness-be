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
 * Đặt lịch gói thường. Đặt lịch trừ buổi ngay; hủy hợp lệ thì hoàn lại, không đến vẫn mất buổi.
 */
class BookingService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly RedlightService $redlightService,
    ) {}

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

            if ($package->remaining_credits !== null) {
                $package->update(['remaining_credits' => $package->remaining_credits - 1]);
            }

            return Booking::query()->create([
                'user_id' => $member->id,
                'user_package_id' => $package->id,
                'class_schedule_id' => $lockedSchedule->id,
                'status' => BookingStatus::Confirmed,
                'credit_deducted_at' => now(),
            ]);
        });
    }

    public function cancelByMember(Booking $booking, User $member): Booking
    {
        return DB::transaction(function () use ($booking, $member) {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $locked->status->is(BookingStatus::Confirmed)) {
                throw new GymException(__('messages.gym_booking_already_cancelled'));
            }

            if (! $this->canMemberCancel($locked->classSchedule)) {
                throw new GymException(__('messages.gym_booking_cancel_locked', ['hours' => config('gym.cancel_lock_hours')]));
            }

            $this->markCancelled($locked, $member);

            if ($locked->redlight_paid_at !== null) {
                $this->notifyCancelled($locked, 'messages.gym_notify_member_cancelled_redlight');
            }

            return $locked;
        });
    }

    /**
     * Nhân viên hủy thì hoàn buổi trong mọi trường hợp, trừ khi lớp đã qua giờ.
     */
    public function cancelByStaff(Booking $booking, User $staff): Booking
    {
        return DB::transaction(function () use ($booking, $staff) {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if (! $locked->status->is(BookingStatus::Confirmed)) {
                throw new GymException(__('messages.gym_booking_already_cancelled'));
            }

            if ($locked->classSchedule->end_time->lte(now())) {
                throw new GymException(__('messages.gym_booking_class_finished'));
            }

            $this->markCancelled($locked, $staff);
            $this->notifyCancelledByStaff($locked);

            return $locked;
        });
    }

    /**
     * Hủy cả lớp: mọi booking bị hủy và được hoàn buổi. Lớp đã qua giờ thì giữ nguyên booking.
     */
    public function cancelSchedule(ClassSchedule $schedule, User $staff): ClassSchedule
    {
        return DB::transaction(function () use ($schedule, $staff) {
            $schedule->update(['status' => ClassScheduleStatus::Cancelled]);

            if ($schedule->end_time->lte(now())) {
                return $schedule;
            }

            $schedule->confirmedBookings()->lockForUpdate()->get()->each(function (Booking $booking) use ($staff) {
                $this->markCancelled($booking, $staff);
                $this->notifyCancelledByStaff($booking);
            });

            return $schedule;
        });
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

    private function notifyCancelledByStaff(Booking $booking): void
    {
        $this->notifyCancelled($booking, $booking->redlight_paid_at !== null
            ? 'messages.gym_notify_booking_cancelled_redlight'
            : 'messages.gym_notify_booking_cancelled');
    }

    private function notifyCancelled(Booking $booking, string $messageKey): void
    {
        $schedule = $booking->classSchedule;

        $this->notificationService->send($booking->user_id, NotificationType::Schedule, __($messageKey, [
            'name' => $schedule->fitnessClass->name,
            'time' => $schedule->start_time->format('H:i d/m/Y'),
        ]));
    }

    private function markCancelled(Booking $booking, User $by): Booking
    {
        $this->redlightService->cancelPendingOrders(collect([$booking->id]));

        /** @var UserPackage $package */
        $package = UserPackage::query()->lockForUpdate()->findOrFail($booking->user_package_id);

        if ($package->remaining_credits !== null) {
            $package->update(['remaining_credits' => $package->remaining_credits + 1]);
        }

        $booking->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $by->id,
            'credit_deducted_at' => null,
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

        if ($package->remaining_credits !== null && $package->remaining_credits <= 0) {
            throw new GymException(__('messages.gym_booking_no_credits'));
        }
    }
}
