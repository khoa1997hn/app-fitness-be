<?php

namespace App\Share\Models;

use App\Share\Attributes\File;
use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $program_id
 * @property LessonType $type
 * @property Level|null $level
 * @property int $day
 * @property string $name
 * @property string|null $description
 * @property File|null $thumbnail
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Program $program
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Video> $videos
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $favorites
 */
class Lesson extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'program_id',
        'type',
        'level',
        'day',
    ];

    protected $translatedAttributes = [
        'name',
        'description',
        'thumbnail',
    ];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'level' => Level::class,
            'day' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lesson_favorites');
    }
}
