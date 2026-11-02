<?php

namespace App\Share\Models;

use App\Share\Enums\PackageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gói hội viên đã mua. Các field của gói được chụp lại lúc cấp, sửa gói gốc không ảnh hưởng.
 *
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property int $order_id
 * @property string $name
 * @property PackageType $package_type
 * @property int|null $total_credits null = không giới hạn
 * @property int|null $remaining_credits
 * @property int $session_duration_minutes
 * @property bool $is_off_peak
 * @property int $base_people_max
 * @property int $extra_people_session_max
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property-read User $user
 */
class UserPackage extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'order_id',
        'name',
        'package_type',
        'total_credits',
        'remaining_credits',
        'session_duration_minutes',
        'is_off_peak',
        'base_people_max',
        'extra_people_session_max',
        'start_date',
        'end_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function privateRequests(): HasMany
    {
        return $this->hasMany(PrivateRequest::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    protected function casts(): array
    {
        return [
            'package_type' => PackageType::class,
            'total_credits' => 'integer',
            'remaining_credits' => 'integer',
            'session_duration_minutes' => 'integer',
            'is_off_peak' => 'boolean',
            'base_people_max' => 'integer',
            'extra_people_session_max' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
