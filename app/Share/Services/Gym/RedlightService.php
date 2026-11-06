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

/**
 * Lượt Redlight: mua trước theo tài khoản, dùng cho lịch lớp nhóm nào thì trừ 1 lượt.
 * Không lưu số dư, tính từ đơn đã thanh toán và lịch đang giữ Redlight. Lịch bị hủy tự hoàn lượt vì không còn được tính.
 */
class RedlightService
{
    /**
     * @return array{purchased_total: int, used: int, available: int}
     */
    public function summary(User $member): array
    {
        $purchased = (int) Order::query()
            ->where('user_id', $member->id)
            ->where('type', OrderType::Redlight)
            ->where('status', OrderStatus::Success)
            ->sum('quantity');

        // Gồm cả buổi đã học và buổi vắng mặt, chỉ lịch bị hủy mới được hoàn
        $used = Booking::query()
            ->where('user_id', $member->id)
            ->where('status', BookingStatus::Confirmed)
            ->where('has_redlight', true)
            ->count();

        return [
            'purchased_total' => $purchased,
            'used' => $used,
            'available' => $purchased - $used,
        ];
    }

    public function isAvailable(ClassSchedule $schedule): bool
    {
        return $schedule->status->is(ClassScheduleStatus::Active) && $schedule->start_time->isFuture();
    }

    /**
     * Gọi trong transaction. Khóa dòng hội viên để hai yêu cầu song song không dùng chung một lượt.
     */
    public function assertHasCredit(User $member): void
    {
        User::query()->lockForUpdate()->findOrFail($member->id);

        if ($this->summary($member)['available'] < 1) {
            throw new GymException(__('messages.gym_redlight_no_credit'));
        }
    }
}
