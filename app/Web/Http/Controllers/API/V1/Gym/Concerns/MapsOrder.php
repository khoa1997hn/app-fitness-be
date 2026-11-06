<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Concerns;

use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Models\Order;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymOrder',
    required: ['id', 'code', 'type', 'name', 'amount', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'code', type: 'string', example: 'GYM-7K2M9QXA1B'),
        new OA\Property(property: 'type', type: 'string', enum: ['package', 'therapy', 'extra_people', 'event', 'redlight'], example: 'package'),
        new OA\Property(property: 'quantity', description: 'Số suất với đơn người thêm, số lượt với đơn Redlight, số vé với đơn sự kiện', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: '10 classes'),
        new OA\Property(property: 'amount', description: 'Giá chốt lúc tạo đơn (VNĐ)', type: 'integer', example: 2100000),
        new OA\Property(property: 'status', type: 'string', enum: ['pending_terms', 'pending', 'success', 'cancelled'], example: 'pending_terms'),
        new OA\Property(property: 'is_created_by_staff', description: 'Đơn do nhân viên tạo hộ', type: 'boolean', example: false),
        new OA\Property(property: 'buyer_name', type: 'string', nullable: true),
        new OA\Property(property: 'buyer_phone', type: 'string', nullable: true),
        new OA\Property(property: 'buyer_email', type: 'string', nullable: true),
        new OA\Property(property: 'terms_accepted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'payment_method', type: 'string', enum: ['momo', 'vnpay', 'cash'], nullable: true),
        new OA\Property(property: 'transaction_code', type: 'string', nullable: true),
        new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'hold_expires_at', description: 'Hạn giữ vé của đơn sự kiện chưa thanh toán, null với đơn khác', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'GymAdminOrder',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymOrder'),
        new OA\Schema(properties: [
            new OA\Property(property: 'member', properties: [
                new OA\Property(property: 'id', type: 'integer', example: 4),
                new OA\Property(property: 'full_name', type: 'string', example: 'Phạm Thị Member'),
                new OA\Property(property: 'email', type: 'string', example: 'member@demo.kore.vn'),
            ], type: 'object'),
            new OA\Property(property: 'created_by_name', description: 'Nhân viên tạo hộ', type: 'string', nullable: true),
            new OA\Property(property: 'note', description: 'Ghi chú nội bộ', type: 'string', nullable: true),
        ]),
    ]
)]
trait MapsOrder
{
    /**
     * @return array<string, mixed>
     */
    private function mapOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'type' => $order->type,
            'event_id' => $order->event_id,
            'quantity' => $order->quantity,
            'name' => $order->name,
            'amount' => $order->amount,
            'status' => $order->status,
            'is_created_by_staff' => $order->created_by !== null,
            'buyer_name' => $order->buyer_name,
            'buyer_phone' => $order->buyer_phone,
            'buyer_email' => $order->buyer_email,
            'terms_accepted_at' => $order->terms_accepted_at,
            'payment_method' => $order->payment_method,
            'transaction_code' => $order->transaction_code,
            'paid_at' => $order->paid_at,
            'hold_expires_at' => $this->holdExpiresAt($order),
            'created_at' => $order->created_at,
        ];
    }

    private function holdExpiresAt(Order $order): mixed
    {
        if (! $order->type->is(OrderType::Event) || ! $order->status->in([OrderStatus::PendingTerms, OrderStatus::Pending])) {
            return null;
        }

        return $order->eventRegistration?->hold_expires_at;
    }

    /**
     * Thêm thông tin chỉ nhân viên được xem. Cần load `user`, `creator`.
     *
     * @return array<string, mixed>
     */
    private function mapAdminOrder(Order $order): array
    {
        return [
            ...$this->mapOrder($order),
            'member' => [
                'id' => $order->user->id,
                'full_name' => trim($order->user->first_name.' '.$order->user->last_name),
                'email' => $order->user->email,
            ],
            'created_by_name' => $order->creator ? trim($order->creator->first_name.' '.$order->creator->last_name) : null,
            'note' => $order->note,
        ];
    }
}
