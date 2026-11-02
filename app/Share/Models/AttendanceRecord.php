<?php

namespace App\Share\Models;

use App\Share\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Điểm danh một booking gói thường. Không ảnh hưởng tới việc trừ buổi.
 *
 * @property int $id
 * @property int $booking_id
 * @property AttendanceStatus $status
 * @property \Carbon\Carbon $attendance_date
 * @property int|null $marked_by
 * @property-read Booking $booking
 */
class AttendanceRecord extends Model
{
    protected $fillable = [
        'booking_id',
        'status',
        'attendance_date',
        'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttendanceStatus::class,
            'attendance_date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
