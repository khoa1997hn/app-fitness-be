<?php

namespace App\Share\Models;

use App\Share\Attributes\File;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
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
}
