<?php

namespace App\Share\Models;

use App\Share\Enums\BookingRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $user_therapy_id
 * @property \Carbon\Carbon $preferred_at
 * @property string|null $note
 * @property BookingRequestStatus $status
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon $created_at
 * @property-read User $user
 * @property-read UserTherapy $userTherapy
 */
class TherapyRequest extends Model
{
    protected $fillable = [
        'user_id',
        'user_therapy_id',
        'preferred_at',
        'note',
        'status',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'preferred_at' => 'datetime',
            'status' => BookingRequestStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userTherapy(): BelongsTo
    {
        return $this->belongsTo(UserTherapy::class);
    }
}
