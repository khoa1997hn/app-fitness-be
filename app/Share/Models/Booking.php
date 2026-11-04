<?php

namespace App\Share\Models;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $user_package_id
 * @property int $class_schedule_id
 * @property BookingStatus $status
 * @property \Carbon\Carbon|null $credit_deducted_at Lúc trừ buổi khi đặt; về null khi hủy và hoàn buổi
 * @property \Carbon\Carbon|null $redlight_paid_at Đã trả tiền dùng Redlight cho buổi này
 * @property \Carbon\Carbon|null $redlight_refunded_at Lúc admin xác nhận đã hoàn tiền Redlight
 * @property int|null $redlight_refunded_by
 * @property \Carbon\Carbon|null $cancelled_at
 * @property int|null $cancelled_by
 * @property-read User $user
 * @property-read UserPackage $userPackage
 * @property-read ClassSchedule $classSchedule
 * @property-read AttendanceRecord|null $attendanceRecord
 */
class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'user_package_id',
        'class_schedule_id',
        'status',
        'credit_deducted_at',
        'redlight_paid_at',
        'redlight_refunded_at',
        'redlight_refunded_by',
        'cancelled_at',
        'cancelled_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'credit_deducted_at' => 'datetime',
            'redlight_paid_at' => 'datetime',
            'redlight_refunded_at' => 'datetime',
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

    public function pendingRedlightOrder(): HasOne
    {
        return $this->hasOne(Order::class)
            ->where('type', OrderType::Redlight)
            ->where('status', OrderStatus::Pending);
    }

    public function redlightOrders(): HasMany
    {
        return $this->hasMany(Order::class)->where('type', OrderType::Redlight);
    }

    /**
     * `pending` đã trả Redlight mà lịch bị hủy, chờ hoàn; `refunded` đã hoàn; null không cần hoàn.
     */
    public function redlightRefundStatus(): ?string
    {
        if ($this->redlight_paid_at === null || ! $this->status->is(BookingStatus::Cancelled)) {
            return null;
        }

        return $this->redlight_refunded_at === null ? 'pending' : 'refunded';
    }

    public function attendanceRecord(): HasOne
    {
        return $this->hasOne(AttendanceRecord::class);
    }
}
