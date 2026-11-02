<?php

namespace App\Share\Models;

use App\Share\Enums\ScheduleCancelRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Yêu cầu hủy lịch dạy của HLV, gắn với lớp hoặc buổi Private.
 *
 * @property int $id
 * @property int|null $class_schedule_id
 * @property int|null $private_request_id
 * @property int $trainer_id
 * @property string $reason
 * @property ScheduleCancelRequestStatus $status
 * @property int|null $reviewed_by
 * @property string|null $review_note
 * @property-read ClassSchedule|null $classSchedule
 * @property-read PrivateRequest|null $privateRequest
 * @property-read User $trainer
 */
class ScheduleCancelRequest extends Model
{
    protected $fillable = [
        'class_schedule_id',
        'private_request_id',
        'trainer_id',
        'reason',
        'status',
        'reviewed_by',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScheduleCancelRequestStatus::class,
        ];
    }

    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function privateRequest(): BelongsTo
    {
        return $this->belongsTo(PrivateRequest::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
