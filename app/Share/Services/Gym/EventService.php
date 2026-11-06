<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\EventRegistrationStatus;
use App\Share\Enums\EventStatus;
use App\Share\Enums\NotificationType;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\Event;
use App\Share\Models\EventRegistration;
use App\Share\Models\Order;
use App\Share\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Vé sự kiện. Vé có phí được giữ khi tạo đơn, quá hạn thì tự nhả. Học viên tự hủy được vé chưa thanh toán.
 */
class EventService
{
    public const MAX_TICKETS_PER_REGISTRATION = 20;

    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * Gọi trong transaction. Khóa dòng sự kiện để không bán vượt số vé.
     */
    public function lockForSale(int $eventId, User $member, int $quantity): Event
    {
        /** @var Event $event */
        $event = Event::query()->lockForUpdate()->findOrFail($eventId);

        if (! $event->status->is(EventStatus::Open) || $event->event_date->isPast()) {
            throw new GymException(__('messages.gym_event_not_on_sale'));
        }

        if ($quantity > self::MAX_TICKETS_PER_REGISTRATION) {
            throw new GymException(__('messages.gym_event_quantity_limit', ['max' => self::MAX_TICKETS_PER_REGISTRATION]));
        }

        $alreadyRegistered = $this->activeRegistrations($event)->where('user_id', $member->id)->exists();

        if ($alreadyRegistered) {
            throw new GymException(__('messages.gym_event_already_registered'));
        }

        if ($this->reservedTickets($event) + $quantity > $event->total_tickets) {
            throw new GymException(__('messages.gym_event_sold_out'));
        }

        return $event;
    }

    public function registerFree(Event $event, User $member, int $quantity): EventRegistration
    {
        return DB::transaction(function () use ($event, $member, $quantity) {
            $locked = $this->lockForSale($event->id, $member, $quantity);

            if ($locked->ticket_price > 0) {
                throw new GymException(__('messages.gym_event_free_only'));
            }

            $registration = $this->createRegistration($locked, $member, $quantity, [
                'status' => EventRegistrationStatus::Paid,
                'paid_at' => now(),
            ]);

            $this->notificationService->send($member->id, NotificationType::Event, __('messages.gym_notify_event_registered', [
                'name' => $locked->name,
                'code' => $registration->ticket_code,
            ]));

            return $registration;
        });
    }

    public function hold(Event $event, User $member, Order $order, int $quantity): EventRegistration
    {
        return $this->createRegistration($event, $member, $quantity, [
            'order_id' => $order->id,
            'status' => EventRegistrationStatus::Pending,
            'hold_expires_at' => now()->addMinutes((int) config('gym.event_hold_minutes')),
        ]);
    }

    public function assertHoldValid(Order $order): void
    {
        $registration = $this->registrationOf($order);

        if (! $registration->status->is(EventRegistrationStatus::Pending) || $registration->hold_expires_at->isPast()) {
            throw new GymException(__('messages.gym_event_hold_expired'));
        }
    }

    public function markPaid(Order $order): void
    {
        $registration = $this->registrationOf($order);
        $registration->update([
            'status' => EventRegistrationStatus::Paid,
            'hold_expires_at' => null,
            'paid_at' => now(),
        ]);

        $this->notificationService->send($order->user_id, NotificationType::Event, __('messages.gym_notify_event_paid', [
            'name' => $registration->event->name,
            'code' => $registration->ticket_code,
        ]));
    }

    public function checkIn(EventRegistration $registration, User $staff): EventRegistration
    {
        return DB::transaction(function () use ($registration, $staff) {
            /** @var EventRegistration $locked */
            $locked = EventRegistration::query()->lockForUpdate()->findOrFail($registration->id);

            if (! $locked->status->is(EventRegistrationStatus::Paid)) {
                throw new GymException(__('messages.gym_checkin_not_paid'));
            }

            if ($locked->checked_in_at !== null) {
                throw new GymException(__('messages.gym_checkin_duplicate'));
            }

            $locked->update(['checked_in_at' => now(), 'checked_in_by' => $staff->id]);

            return $locked;
        });
    }

    /**
     * Mở hoặc đóng bán vé. Hủy là trạng thái cuối, vé đã thanh toán hoàn tiền thủ công.
     */
    public function changeStatus(Event $event, string $status, ?string $reason): Event
    {
        return DB::transaction(function () use ($event, $status, $reason) {
            /** @var Event $locked */
            $locked = Event::query()->lockForUpdate()->findOrFail($event->id);

            if ($locked->status->is(EventStatus::Cancelled) || $locked->status->is($status)) {
                throw new GymException(__('messages.gym_event_status_invalid'));
            }

            if ($status === EventStatus::Cancelled) {
                $this->cancelRegistrations($locked, $reason);
            }

            $locked->update([
                'status' => $status,
                'cancel_reason' => $status === EventStatus::Cancelled ? $reason : null,
            ]);

            return $locked;
        });
    }

    /**
     * Sửa số vé phải giữ đủ cho vé đã bán và đang giữ.
     */
    public function assertTotalTicketsValid(Event $event, int $totalTickets): void
    {
        $reserved = $this->reservedTickets($event);

        if ($totalTickets < $reserved) {
            throw new GymException(__('messages.gym_event_tickets_below_reserved', ['count' => $reserved]));
        }
    }

    /**
     * Nhả vé quá hạn giữ chỗ. Chạy lại an toàn vì chỉ xử lý vé còn `pending`.
     *
     * @return int Số lượt đã nhả
     */
    public function releaseExpiredHolds(): int
    {
        $ids = EventRegistration::query()
            ->where('status', EventRegistrationStatus::Pending)
            ->where('hold_expires_at', '<=', now())
            ->pluck('id');

        $released = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$released) {
                /** @var EventRegistration|null $registration */
                $registration = EventRegistration::query()
                    ->where('status', EventRegistrationStatus::Pending)
                    ->where('hold_expires_at', '<=', now())
                    ->lockForUpdate()
                    ->find($id);

                if (! $registration) {
                    return;
                }

                $this->release($registration);

                $this->notificationService->send($registration->user_id, NotificationType::Event, __('messages.gym_notify_event_hold_expired', [
                    'name' => $registration->event->name,
                ]));

                $released++;
            });
        }

        return $released;
    }

    /**
     * Học viên tự hủy đơn vé chưa thanh toán, vé trả lại ngay.
     */
    public function cancelHold(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $locked->type->is(OrderType::Event)) {
                throw new GymException(__('messages.gym_order_cancel_not_allowed'));
            }

            if ($locked->status->is(OrderStatus::Cancelled)) {
                return $locked;
            }

            if ($locked->status->is(OrderStatus::Success)) {
                throw new GymException(__('messages.gym_order_cancel_not_allowed'));
            }

            /** @var EventRegistration $registration */
            $registration = EventRegistration::query()->where('order_id', $locked->id)->lockForUpdate()->firstOrFail();
            $this->release($registration);

            return $locked->refresh();
        });
    }

    public function reservedTickets(Event $event): int
    {
        return (int) $this->activeRegistrations($event)->sum('quantity');
    }

    private function activeRegistrations(Event $event)
    {
        return $event->registrations()->where(fn ($query) => $query
            ->where('status', EventRegistrationStatus::Paid)
            ->orWhere(fn ($inner) => $inner
                ->where('status', EventRegistrationStatus::Pending)
                ->where('hold_expires_at', '>', now())));
    }

    private function cancelRegistrations(Event $event, ?string $reason): void
    {
        $registrations = $event->registrations()
            ->whereIn('status', [EventRegistrationStatus::Paid, EventRegistrationStatus::Pending])
            ->get();

        $message = __('messages.gym_notify_event_cancelled', [
            'name' => $event->name,
            'reason' => $reason ? __('messages.gym_notify_event_cancelled_reason', ['reason' => $reason]) : '',
        ]);

        foreach ($registrations as $registration) {
            $registration->update(['status' => EventRegistrationStatus::Cancelled]);
            Order::query()->whereKey($registration->order_id)
                ->whereIn('status', [OrderStatus::PendingTerms, OrderStatus::Pending])
                ->update(['status' => OrderStatus::Cancelled]);

            $this->notificationService->send($registration->user_id, NotificationType::Event, $message);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRegistration(Event $event, User $member, int $quantity, array $attributes): EventRegistration
    {
        /** @var EventRegistration $registration */
        $registration = $event->registrations()->create([
            'user_id' => $member->id,
            'quantity' => $quantity,
            ...$attributes,
        ]);

        $registration->update(['ticket_code' => "EVT-{$event->id}-{$registration->id}"]);

        return $registration;
    }

    private function release(EventRegistration $registration): void
    {
        $registration->update(['status' => EventRegistrationStatus::Cancelled]);
        Order::query()->whereKey($registration->order_id)
            ->whereIn('status', [OrderStatus::PendingTerms, OrderStatus::Pending])
            ->update(['status' => OrderStatus::Cancelled]);
    }

    private function registrationOf(Order $order): EventRegistration
    {
        return EventRegistration::query()->where('order_id', $order->id)->firstOrFail();
    }
}
