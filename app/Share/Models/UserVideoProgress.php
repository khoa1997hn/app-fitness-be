<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $video_id
 * @property int $watched_percent
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
        'watched_percent',
    ];

    protected function casts(): array
    {
        return [
            'watched_percent' => 'integer',
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
