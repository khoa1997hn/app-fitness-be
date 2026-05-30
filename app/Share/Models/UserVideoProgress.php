<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property int $watched_seconds
 * @property bool $is_completed
 * @property ?\Carbon\Carbon $last_watched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read Video $video
 */
class UserVideoProgress extends Model
{
    protected $table = 'user_video_progress';

    protected $fillable = [
        'user_id',
        'video_id',
        'watched_seconds',
        'is_completed',
        'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'watched_seconds' => 'integer',
            'is_completed' => 'boolean',
            'last_watched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
