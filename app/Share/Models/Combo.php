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
 * @property string $name
 * @property File|null $cover
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Program> $programs
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ComboInfo> $infos
 */
class Combo extends Model implements TranslatableContract
{
    use Translatable;

    protected $translatedAttributes = [
        'name',
        'cover',
    ];

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'combo_program')
            ->withPivot('sort')
            ->orderByPivot('sort');
    }

    public function infos(): HasMany
    {
        return $this->hasMany(ComboInfo::class)->orderBy('sort');
    }
}
