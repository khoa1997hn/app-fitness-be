<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\EventRegistrationStatus;
use App\Share\Enums\EventStatus;
use App\Share\Enums\NotificationType;
use App\Share\Models\Booking;
use App\Share\Models\EventRegistration;
use App\Share\Models\PrivateRequest;
use App\Share\Models\TherapyRequest;
use App\Share\Models\UserNotification;
use App\Share\Models\UserPackage;
use App\Share\Models\UserTherapy;

class NotificationService
{
    public function send(int $userId, string $type, string $message): UserNotification
    {
        return UserNotification::query()->create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Nhắc lịch sắp tới và gói sắp hết hạn. Chạy lại an toàn nhờ `dedupe_key`.
     *
     * @return int Số thông báo mới
     */
    public function sendReminders(): int
    {
        return $this->remindBookings()
            + $this->remindPrivateRequests()
            + $this->remindTherapyRequests()
            + $this->remindEvents()
            + $this->noticeExpiringPackages()
            + $this->noticeExpiringTherapies();
    }

    private function remindBookings(): int
    {
        $sent = 0;

        Booking::query()
            ->with('classSchedule.fitnessClass')
            ->where('status', BookingStatus::Confirmed)
            ->whereHas('classSchedule', fn ($query) => $query
                ->where('status', ClassScheduleStatus::Active)
                ->whereBetween('start_time', $this->reminderWindow()))
            ->get()
            ->each(function (Booking $booking) use (&$sent) {
                $sent += $this->sendOnce($booking->user_id, NotificationType::Schedule, "booking-{$booking->id}", __('messages.gym_notify_reminder', [
                    'name' => $booking->classSchedule->fitnessClass->name,
                    'time' => $booking->classSchedule->start_time->format('H:i d/m/Y'),
                ]));
            });

        return $sent;
    }

    private function remindPrivateRequests(): int
    {
        $sent = 0;

        PrivateRequest::query()
            ->where('status', BookingRequestStatus::Confirmed)
            ->whereBetween('requested_at', $this->reminderWindow())
            ->get()
            ->each(function (PrivateRequest $request) use (&$sent) {
                $sent += $this->sendOnce($request->user_id, NotificationType::Schedule, "private-{$request->id}-{$request->requested_at->format('YmdHi')}", __('messages.gym_notify_reminder', [
                    'name' => 'Private '.$request->discipline,
                    'time' => $request->requested_at->format('H:i d/m/Y'),
                ]));
            });

        return $sent;
    }

    private function remindTherapyRequests(): int
    {
        $sent = 0;

        TherapyRequest::query()
            ->with('userTherapy')
            ->where('status', BookingRequestStatus::Confirmed)
            ->whereBetween('preferred_at', $this->reminderWindow())
            ->get()
            ->each(function (TherapyRequest $request) use (&$sent) {
                $sent += $this->sendOnce($request->user_id, NotificationType::Schedule, "therapy-{$request->id}", __('messages.gym_notify_reminder', [
                    'name' => $request->userTherapy->name,
                    'time' => $request->preferred_at->format('H:i d/m/Y'),
                ]));
            });

        return $sent;
    }

    private function remindEvents(): int
    {
        $sent = 0;

        EventRegistration::query()
            ->with('event')
            ->where('status', EventRegistrationStatus::Paid)
            ->whereHas('event', fn ($query) => $query
                ->where('status', '!=', EventStatus::Cancelled)
                ->whereBetween('event_date', $this->reminderWindow()))
            ->get()
            ->each(function (EventRegistration $registration) use (&$sent) {
                $sent += $this->sendOnce($registration->user_id, NotificationType::Event, "event-{$registration->id}", __('messages.gym_notify_reminder', [
                    'name' => $registration->event->name,
                    'time' => $registration->event->event_date->format('H:i d/m/Y'),
                ]));
            });

        return $sent;
    }

    private function noticeExpiringPackages(): int
    {
        $sent = 0;

        UserPackage::query()
            ->whereBetween('end_date', $this->expiryWindow())
            ->where(fn ($query) => $query->whereNull('remaining_credits')->orWhere('remaining_credits', '>', 0))
            ->get()
            ->each(function (UserPackage $package) use (&$sent) {
                $date = $package->end_date->toDateString();
                $sent += $this->sendOnce($package->user_id, NotificationType::Package, "package-{$package->id}-{$date}", __('messages.gym_notify_package_expiring', [
                    'name' => $package->name,
                    'date' => $package->end_date->format('d/m/Y'),
                ]));
            });

        return $sent;
    }

    private function noticeExpiringTherapies(): int
    {
        $sent = 0;

        UserTherapy::query()
            ->whereBetween('end_date', $this->expiryWindow())
            ->where('sessions_remaining', '>', 0)
            ->get()
            ->each(function (UserTherapy $therapy) use (&$sent) {
                $date = $therapy->end_date->toDateString();
                $sent += $this->sendOnce($therapy->user_id, NotificationType::Package, "therapy-package-{$therapy->id}-{$date}", __('messages.gym_notify_package_expiring', [
                    'name' => $therapy->name,
                    'date' => $therapy->end_date->format('d/m/Y'),
                ]));
            });

        return $sent;
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function reminderWindow(): array
    {
        return [now(), now()->addHours((int) config('gym.reminder_hours'))];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function expiryWindow(): array
    {
        return [now()->toDateString(), now()->addDays((int) config('gym.package_expiry_notice_days'))->toDateString()];
    }

    private function sendOnce(int $userId, string $type, string $key, string $message): int
    {
        return UserNotification::query()->firstOrCreate(
            ['user_id' => $userId, 'dedupe_key' => $key],
            ['type' => $type, 'message' => $message],
        )->wasRecentlyCreated ? 1 : 0;
    }
}
