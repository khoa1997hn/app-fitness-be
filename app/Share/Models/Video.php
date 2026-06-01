<?php

namespace App\Share\Models;

use App\Share\Attributes\File;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $lesson_id
 * @property File $file
 * @property int $duration_seconds
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Lesson $lesson
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserVideoProgress> $progresses
 */
class Video extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'lesson_id',
    ];

    protected $translatedAttributes = [
        'file',
        'duration_seconds',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function progresses(): HasMany
    {
        return $this->hasMany(UserVideoProgress::class);
    }
}
