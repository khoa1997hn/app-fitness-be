<?php

namespace App\Share\Models;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\PrivateDiscipline;
use App\Share\Enums\ScheduleCancelRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $user_package_id
 * @property PrivateDiscipline $discipline
 * @property \Carbon\Carbon $requested_at
 * @property int $people_count
 * @property int $extra_people_count Trừ vào số dư slot của tài khoản khi hoàn thành
 * @property int|null $trainer_id
 * @property string|null $note
 * @property BookingRequestStatus $status
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|null $completed_at
 * @property bool $skip_auto_complete Admin đã bỏ hoàn thành thì hệ thống không tự hoàn thành lại
 * @property \Carbon\Carbon $created_at
 * @property-read User $user
 * @property-read UserPackage $userPackage
 * @property-read User|null $trainer
 */
class PrivateRequest extends Model
{
    protected $fillable = [
        'user_id',
        'user_package_id',
        'discipline',
        'requested_at',
        'people_count',
        'extra_people_count',
        'trainer_id',
        'note',
        'status',
        'reviewed_by',
        'completed_at',
        'skip_auto_complete',
    ];

    protected function casts(): array
    {
        return [
            'discipline' => PrivateDiscipline::class,
            'requested_at' => 'datetime',
            'people_count' => 'integer',
            'extra_people_count' => 'integer',
            'status' => BookingRequestStatus::class,
            'completed_at' => 'datetime',
            'skip_auto_complete' => 'boolean',
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

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function pendingCancelRequests(): HasMany
    {
        return $this->hasMany(ScheduleCancelRequest::class)->where('status', ScheduleCancelRequestStatus::Pending);
    }
}
