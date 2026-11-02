<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\Order;
use App\Share\Models\User;
use Illuminate\Support\Collection;

/**
 * Redlight tính theo từng buổi, chỉ cho lớp Barre, Pilates. Lịch đã trả tiền mà bị hủy thì hoàn tiền thủ công.
 */
class RedlightService
{
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

    private function assertBookingEligible(Booking $booking): void
    {
        if (! $booking->status->is(BookingStatus::Confirmed)
            || $booking->redlight_paid_at !== null
            || ! $this->isAvailable($booking->classSchedule)) {
            throw new GymException(__('messages.gym_redlight_unavailable'));
        }
    }
}
