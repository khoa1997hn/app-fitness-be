<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Concerns;

use App\Share\Enums\EventRegistrationStatus;
use App\Share\Enums\OrderStatus;
use App\Share\Models\Event;
use App\Share\Models\EventRegistration;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymEvent',
    required: ['id', 'name', 'event_date', 'location', 'ticket_price', 'total_tickets', 'sold_tickets', 'tickets_left', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Workshop Pilates chuyên sâu'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'event_date', description: 'Giờ địa phương', type: 'string', example: '2026-12-20T09:00:00'),
        new OA\Property(property: 'location', type: 'string', example: 'Cơ sở Hải Phòng'),
        new OA\Property(property: 'ticket_price', description: '0 là miễn phí', type: 'integer', example: 300000),
        new OA\Property(property: 'total_tickets', type: 'integer', example: 30),
        new OA\Property(property: 'sold_tickets', description: 'Số vé đã thanh toán', type: 'integer', example: 12),
        new OA\Property(property: 'tickets_left', description: 'Còn lại sau khi trừ vé đã bán và đang giữ', type: 'integer', example: 16),
        new OA\Property(property: 'status', type: 'string', enum: ['open', 'closed', 'cancelled'], example: 'open'),
    ]
)]
#[OA\Schema(
    schema: 'GymEventRegistration',
    required: ['id', 'event_id', 'quantity', 'ticket_code', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 15),
        new OA\Property(property: 'event_id', type: 'integer', example: 2),
        new OA\Property(property: 'event', ref: '#/components/schemas/GymEvent'),
        new OA\Property(property: 'quantity', type: 'integer', example: 1),
        new OA\Property(property: 'ticket_code', type: 'string', example: 'EVT-2-15'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'paid', 'cancelled'], example: 'paid'),
        new OA\Property(property: 'order_code', description: 'Null với vé miễn phí', type: 'string', nullable: true),
        new OA\Property(property: 'hold_expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'checked_in_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
trait MapsEvent
{
    /**
     * Cần query qua scope `withTicketCounts`.
     *
     * @return array<string, mixed>
     */
    private function mapEvent(Event $event): array
    {
        $sold = (int) $event->sold_tickets;
        $held = (int) $event->held_tickets;

        return [
            'id' => $event->id,
            'name' => $event->name,
            'description' => $event->description,
            'event_date' => $event->event_date->format('Y-m-d\TH:i:s'),
            'location' => $event->location,
            'ticket_price' => $event->ticket_price,
            'total_tickets' => $event->total_tickets,
            'sold_tickets' => $sold,
            'held_tickets' => $held,
            'tickets_left' => max(0, $event->total_tickets - $sold - $held),
            'status' => $event->status,
        ];
    }

    /**
     * Cần load `order`.
     *
     * @return array<string, mixed>
     */
    private function mapRegistration(EventRegistration $registration): array
    {
        return [
            'id' => $registration->id,
            'event_id' => $registration->event_id,
            'quantity' => $registration->quantity,
            'ticket_code' => $registration->ticket_code,
            'status' => $registration->status,
            'order_code' => $registration->order?->code,
            'hold_expires_at' => $registration->hold_expires_at,
            'paid_at' => $registration->paid_at,
            'checked_in_at' => $registration->checked_in_at,
        ];
    }

    /**
     * Thêm thông tin chỉ nhân viên được xem. Cần load `user`, `order`.
     *
     * @return array<string, mixed>
     */
    private function mapAdminRegistration(EventRegistration $registration): array
    {
        return [
            ...$this->mapRegistration($registration),
            'user_id' => $registration->user_id,
            'member_name' => trim($registration->user->first_name.' '.$registration->user->last_name),
            'member_phone' => $registration->order?->buyer_phone ?? $registration->user->phone,
            'member_email' => $registration->user->email,
            'needs_refund' => $registration->status->is(EventRegistrationStatus::Cancelled)
                && $registration->order?->status->is(OrderStatus::Success) === true,
        ];
    }
}
