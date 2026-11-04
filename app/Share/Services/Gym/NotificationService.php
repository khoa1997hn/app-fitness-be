<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\EventRegistrationStatus;
use App\Share\Enums\EventStatus;
use App\Share\Enums\NotificationType;
use App\Share\Enums\OrderType;
use App\Share\Enums\PaymentMethod;
use App\Share\Enums\Role;
use App\Share\Enums\UserStatus;
use App\Share\Mail\GymNoticeMail;
use App\Share\Mail\ScheduleCancelledMail;
use App\Share\Models\Booking;
use App\Share\Models\EventRegistration;
use App\Share\Models\Order;
use App\Share\Models\PrivateRequest;
use App\Share\Models\TherapyRequest;
use App\Share\Models\User;
use App\Share\Models\UserNotification;
use App\Share\Models\UserPackage;
use App\Share\Models\UserTherapy;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;

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
     * Phòng tập hủy lịch: báo trong tài khoản và gửi email cho học viên.
     * Email vào hàng đợi sau khi giao dịch hủy thành công, lỗi gửi mail không ảnh hưởng việc hủy.
     */
    public function sendScheduleCancelled(int $userId, string $message, string $messageEn, string $messageVi): UserNotification
    {
        $notification = $this->send($userId, NotificationType::Schedule, $message);
        $user = User::query()->find($userId);

        if ($user !== null) {
            Mail::to($user->email)->queue(new ScheduleCancelledMail($user->first_name, $messageVi, $messageEn));
        }

        return $notification;
    }

    /**
     * Có đơn Private mới cần duyệt: gửi email cho các admin đang hoạt động.
     */
    public function emailAdminsPrivatePending(PrivateRequest $request): void
    {
        $member = $request->user;
        $details = [
            'member' => trim($member->first_name.' '.$member->last_name),
            'name' => 'Private '.$request->discipline,
            'time' => $request->requested_at->format('H:i d/m/Y'),
            'people' => $request->people_count + $request->extra_people_count,
        ];

        User::query()
            ->where('role', Role::Admin)
            ->where('status', UserStatus::Active)
            ->get()
            ->each(fn (User $admin) => Mail::to($admin->email)->queue(new GymNoticeMail(
                __('messages.gym_mail_private_pending_subject', [], 'vi'),
                $admin->first_name,
                __('messages.gym_mail_private_pending', $details, 'vi'),
                titleEn: __('messages.gym_mail_private_pending_subject', [], 'en'),
                noticeEn: __('messages.gym_mail_private_pending', $details, 'en'),
                actionPath: '/admin/bookings/requests/'.$request->id,
            )));
    }

    /**
     * Đơn thanh toán thành công: gửi email cho người mua và các admin đang hoạt động.
     * Người mua nhận ở email đã điền khi ký điều khoản, đơn không có thì dùng email tài khoản.
     */
    public function emailOrderPaid(Order $order): void
    {
        $member = $order->user;
        $details = [
            'code' => $order->code,
            'name' => $order->name,
            'amount' => number_format($order->amount, 0, ',', '.').'đ',
            'time' => $order->paid_at->format('H:i d/m/Y'),
            'member' => trim($member->first_name.' '.$member->last_name),
        ];
        $ticketCode = EventRegistration::query()->where('order_id', $order->id)->value('ticket_code');
        $hasSignedTerms = $order->terms_signature !== null;
        $buyerNotices = [];
        $adminNotices = [];

        foreach (['vi', 'en'] as $locale) {
            $localizedDetails = [...$details, 'method' => __('enums.'.PaymentMethod::class.'.'.$order->payment_method->value, [], $locale)];
            $buyerNotices[$locale] = __('messages.gym_mail_order_paid', $localizedDetails, $locale);
            $adminNotices[$locale] = __('messages.gym_mail_order_paid_admin', $localizedDetails, $locale);

            if ($ticketCode !== null) {
                $buyerNotices[$locale] .= ' '.__('messages.gym_mail_order_paid_ticket', ['code' => $ticketCode], $locale);
            }

            if ($hasSignedTerms) {
                $buyerNotices[$locale] .= ' '.__('messages.gym_mail_order_paid_terms', [], $locale);
            }
        }

        Mail::to($order->buyer_email ?: $member->email)->queue(new GymNoticeMail(
            __('messages.gym_mail_order_paid_subject', [], 'vi'),
            $order->buyer_name ?: $member->first_name,
            $buyerNotices['vi'],
            $hasSignedTerms ? $order->id : null,
            titleEn: __('messages.gym_mail_order_paid_subject', [], 'en'),
            noticeEn: $buyerNotices['en'],
            actionPath: match ($order->type->value) {
                OrderType::Event => '/bloom/tai-khoan',
                OrderType::Therapy => '/hoc-vien/khoa-hoc-cua-toi?tab=therapy',
                OrderType::ExtraPeople => '/hoc-vien/khoa-hoc-cua-toi?tab=addons',
                OrderType::Redlight => '/hoc-vien/khoa-hoc-cua-toi?tab=history',
                default => '/hoc-vien/khoa-hoc-cua-toi',
            },
        ));

        User::query()
            ->where('role', Role::Admin)
            ->where('status', UserStatus::Active)
            ->get()
            ->each(fn (User $admin) => Mail::to($admin->email)->queue(new GymNoticeMail(
                __('messages.gym_mail_order_paid_admin_subject', ['code' => $order->code], 'vi'),
                $admin->first_name,
                $adminNotices['vi'],
                titleEn: __('messages.gym_mail_order_paid_admin_subject', ['code' => $order->code], 'en'),
                noticeEn: $adminNotices['en'],
                actionPath: '/admin/payments/'.$order->id,
            )));
    }

    /**
     * HLV được phân vào lớp hoặc buổi Private: gửi email cho HLV.
     */
    public function emailTrainerAssigned(int $trainerId, string $name, CarbonInterface $time, ?string $room = null): void
    {
        $trainer = User::query()->find($trainerId);

        if ($trainer === null) {
            return;
        }

        $details = ['name' => $name, 'time' => $time->format('H:i d/m/Y')];
        $notices = [];

        foreach (['vi', 'en'] as $locale) {
            $notices[$locale] = __('messages.gym_mail_trainer_assigned', $details, $locale);

            if ($room !== null) {
                $notices[$locale] .= ' '.__('messages.gym_mail_trainer_assigned_room', ['room' => $room], $locale);
            }
        }

        Mail::to($trainer->email)->queue(new GymNoticeMail(
            __('messages.gym_mail_trainer_assigned_subject', [], 'vi'),
            $trainer->first_name,
            $notices['vi'],
            titleEn: __('messages.gym_mail_trainer_assigned_subject', [], 'en'),
            noticeEn: $notices['en'],
            actionPath: '/admin/schedules?tab=teaching',
        ));
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
