<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\NotificationType;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\Order;
use App\Share\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Redlight tính theo từng buổi, chỉ cho lớp Barre, Pilates. Lịch đã trả tiền mà bị hủy thì hoàn tiền thủ công, admin xác nhận sau khi hoàn.
 */
class RedlightService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function isAvailable(ClassSchedule $schedule): bool
    {
        return $schedule->status->is(ClassScheduleStatus::Active)
            && $schedule->start_time->isFuture()
            && in_array($schedule->fitnessClass->category->value, config('gym.redlight_categories'), true);
    }

    /**
     * Gọi trong transaction. Lịch của người khác trả 404.
     */
    public function lockForPurchase(User $member, int $bookingId): Booking
    {
        /** @var Booking $booking */
        $booking = Booking::query()
            ->where('user_id', $member->id)
            ->lockForUpdate()
            ->findOrFail($bookingId);

        $this->assertBookingEligible($booking);

        return $booking;
    }

    public function assertPayable(Order $order): void
    {
        if (! $order->booking) {
            throw new GymException(__('messages.gym_redlight_unavailable'));
        }

        $this->assertBookingEligible($order->booking);
    }

    public function markPaid(Order $order): void
    {
        $order->booking->update(['redlight_paid_at' => now()]);
    }

    public function markRefunded(Booking $booking, User $staff): Booking
    {
        return DB::transaction(function () use ($booking, $staff) {
            /** @var Booking $locked */
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($locked->redlightRefundStatus() !== 'pending') {
                throw new GymException(__('messages.gym_redlight_refund_invalid'));
            }

            $locked->update(['redlight_refunded_at' => now(), 'redlight_refunded_by' => $staff->id]);

            $this->notificationService->send($locked->user_id, NotificationType::Schedule, __('messages.gym_notify_redlight_refunded', [
                'name' => $locked->classSchedule->fitnessClass->name,
                'time' => $locked->classSchedule->start_time->format('H:i d/m/Y'),
            ]));

            return $locked;
        });
    }

    /**
     * @param  Collection<int, int>  $bookingIds
     */
    public function cancelPendingOrders(Collection $bookingIds): void
    {
        Order::query()
            ->where('type', OrderType::Redlight)
            ->where('status', OrderStatus::Pending)
            ->whereIn('booking_id', $bookingIds)
            ->update(['status' => OrderStatus::Cancelled]);
    }

    /**
     * Hủy đơn Redlight chờ thanh toán của các lớp đã qua giờ.
     *
     * @return int Số đơn đã hủy
     */
    public function cancelOrdersOfFinishedClasses(): int
    {
        return Order::query()
            ->where('type', OrderType::Redlight)
            ->where('status', OrderStatus::Pending)
            ->whereHas('booking.classSchedule', fn ($query) => $query->where('end_time', '<=', now()))
            ->update(['status' => OrderStatus::Cancelled]);
    }

    private function assertBookingEligible(Booking $booking): void
    {
        if (! $booking->status->is(BookingStatus::Confirmed)
            || $booking->redlight_paid_at !== null
            || ! $this->isAvailable($booking->classSchedule)) {
            throw new GymException(__('messages.gym_redlight_unavailable'));
        }
    }
}
