<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Models\Order;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;

/**
 * Suất người tham gia thêm: mua riêng theo tài khoản, dùng chung cho mọi gói `private_pack`.
 * Không lưu số dư, tính từ đơn đã thanh toán và yêu cầu Private để không thể lệch.
 */
class ExtraPeopleService
{
    /**
     * @return array{purchased_total: int, used: int, reserved: int, balance: int, available: int}
     */
    public function summary(User $member, ?int $exceptRequestId = null): array
    {
        $purchased = (int) Order::query()
            ->where('user_id', $member->id)
            ->where('type', OrderType::ExtraPeople)
            ->where('status', OrderStatus::Success)
            ->sum('quantity');

        $used = (int) PrivateRequest::query()
            ->where('user_id', $member->id)
            ->where('status', BookingRequestStatus::Completed)
            ->sum('extra_people_count');

        $reserved = (int) PrivateRequest::query()
            ->where('user_id', $member->id)
            ->whereIn('status', [BookingRequestStatus::Pending, BookingRequestStatus::Confirmed])
            ->when($exceptRequestId, fn ($query, int $id) => $query->whereKeyNot($id))
            ->sum('extra_people_count');

        $balance = $purchased - $used;

        return [
            'purchased_total' => $purchased,
            'used' => $used,
            'reserved' => $reserved,
            'balance' => $balance,
            'available' => $balance - $reserved,
        ];
    }
}
