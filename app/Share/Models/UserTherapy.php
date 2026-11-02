<?php

namespace App\Share\Models;

use App\Share\Enums\TherapyVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gói trị liệu hội viên đã mua, tách khỏi gói tập.
 *
 * @property int $id
 * @property int $user_id
 * @property int $therapy_service_id
 * @property int $order_id
 * @property string $name
 * @property TherapyVariant $variant
 * @property int $sessions_total
 * @property int $sessions_remaining
 * @property int $session_duration_minutes
 * @property \Carbon\Carbon $end_date
 * @property-read TherapyService $therapyService
 */
class UserTherapy extends Model
{
    protected $table = 'user_therapies';

    protected $fillable = [
        'user_id',
        'therapy_service_id',
        'order_id',
        'name',
        'variant',
        'sessions_total',
        'sessions_remaining',
        'session_duration_minutes',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'variant' => TherapyVariant::class,
            'sessions_total' => 'integer',
            'sessions_remaining' => 'integer',
            'session_duration_minutes' => 'integer',
            'end_date' => 'date',
        ];
    }

    public function therapyService(): BelongsTo
    {
        return $this->belongsTo(TherapyService::class);
    }
}
