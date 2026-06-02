<?php

namespace App\Share\Models;

use App\Share\Attributes\File;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property float|null $rating
 * @property string $name
 * @property string|null $description
 * @property File|null $cover
 * @property int $sort
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Lesson> $lessons
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProgramGoal> $goals
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $favorites
 */
class Program extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'rating',
    ];

    protected $translatedAttributes = [
        'name',
        'description',
        'cover',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
        ];
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(ProgramGoal::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'program_favorites');
    }

    /**
     * Tổng thời lượng (giây) của program = tổng duration_seconds mọi video.
     * duration_seconds nằm ở bảng translation nên không withSum thẳng được;
     * cần eager load lessons.videos.translations rồi cộng dồn (tránh N+1).
     */
    public function totalDurationSeconds(): int
    {
        return (int) $this->lessons
            ->flatMap(fn (Lesson $lesson) => $lesson->videos)
            ->sum('duration_seconds');
    }
}
