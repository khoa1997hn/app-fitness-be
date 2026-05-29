<?php

namespace App\Share\Models;

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $program_id
 * @property LessonType $type
 * @property Level|null $level
 * @property string $name
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Program $program
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Video> $videos
 */
class Lesson extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'program_id',
        'type',
        'level',
    ];

    protected $translatedAttributes = [
        'name',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'level' => Level::class,
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
}
