<?php

namespace App\Share\Models;

use App\Share\Enums\EventRegistrationStatus;
use App\Share\Enums\EventStatus;
use App\Share\Enums\OrderStatus;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sự kiện Bloom. Số vé không lưu riêng, tính từ `event_registrations` để không lệch.
 *
 * @property int $id
 * @property \Carbon\Carbon $event_date
 * @property string $location
 * @property int $ticket_price
 * @property int $total_tickets
 * @property EventStatus $status
 * @property string|null $cancel_reason
 * @property string $name
 * @property \App\Share\Attributes\File|null $image
 * @property string|null $description
 */
class Event extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'event_date',
        'location',
        'ticket_price',
        'total_tickets',
        'status',
        'cancel_reason',
    ];

    protected $translatedAttributes = [
        'name',
        'description',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'ticket_price' => 'integer',
            'total_tickets' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Thêm `sold_tickets`, `held_tickets`. Giữ chỗ quá hạn không còn tính.
     */
    public function scopeWithTicketCounts(Builder $query): Builder
    {
        return $query
            ->withSum(['registrations as sold_tickets' => fn ($inner) => $inner->where('status', EventRegistrationStatus::Paid)], 'quantity')
            ->withSum(['registrations as held_tickets' => fn ($inner) => $inner
                ->where('status', EventRegistrationStatus::Pending)
                ->where('hold_expires_at', '>', now())], 'quantity');
    }

    public function scopeWithRevenue(Builder $query): Builder
    {
        return $query->withSum(['orders as revenue' => fn ($inner) => $inner->where('status', OrderStatus::Success)], 'amount');
    }
}
