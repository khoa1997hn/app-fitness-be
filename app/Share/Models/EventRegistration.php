<?php

namespace App\Share\Models;

use App\Share\Enums\EventRegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vé sự kiện của hội viên. Vé miễn phí không có đơn hàng.
 *
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property int|null $order_id
 * @property int $quantity
 * @property string|null $ticket_code
 * @property EventRegistrationStatus $status
 * @property \Carbon\Carbon|null $hold_expires_at
 * @property \Carbon\Carbon|null $paid_at
 * @property \Carbon\Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property-read Event $event
 * @property-read User $user
 * @property-read Order|null $order
 */
class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'order_id',
        'quantity',
        'ticket_code',
        'status',
        'hold_expires_at',
        'paid_at',
        'checked_in_at',
        'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'status' => EventRegistrationStatus::class,
            'hold_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
