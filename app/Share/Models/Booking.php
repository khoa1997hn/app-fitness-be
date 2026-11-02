<?php

namespace App\Share\Models;

use App\Share\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $user_package_id
 * @property int $class_schedule_id
 * @property BookingStatus $status
 * @property \Carbon\Carbon|null $credit_deducted_at Đã xử lý trừ buổi sau giờ học
 * @property \Carbon\Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property-read User $user
 * @property-read UserPackage $userPackage
 * @property-read ClassSchedule $classSchedule
 */
class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'user_package_id',
        'class_schedule_id',
        'status',
        'credit_deducted_at',
        'cancelled_at',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'credit_deducted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userPackage(): BelongsTo
    {
        return $this->belongsTo(UserPackage::class);
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }
}
