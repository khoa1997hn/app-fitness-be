<?php

namespace App\Share\Models;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\ScheduleCancelRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một buổi trên thời khóa biểu gói thường.
 *
 * @property int $id
 * @property int $fitness_class_id
 * @property int $trainer_id
 * @property string $room
 * @property \Carbon\Carbon $start_time
 * @property \Carbon\Carbon $end_time
 * @property int $max_slot
 * @property ClassScheduleStatus $status
 * @property string|null $notes
 * @property-read FitnessClass $fitnessClass
 * @property-read User $trainer
 */
class ClassSchedule extends Model
{
    protected $fillable = [
        'fitness_class_id',
        'trainer_id',
        'room',
        'start_time',
        'end_time',
        'max_slot',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'max_slot' => 'integer',
            'status' => ClassScheduleStatus::class,
        ];
    }

    public function fitnessClass(): BelongsTo
    {
        return $this->belongsTo(FitnessClass::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function confirmedBookings(): HasMany
    {
        return $this->bookings()->where('status', BookingStatus::Confirmed);
    }

    public function pendingCancelRequests(): HasMany
    {
        return $this->hasMany(ScheduleCancelRequest::class)->where('status', ScheduleCancelRequestStatus::Pending);
    }
}
