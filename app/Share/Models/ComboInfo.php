<?php

namespace App\Share\Models;

use App\Share\Attributes\File;
use App\Share\Casts\FileCast;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $combo_id
 * @property int $sort
 * @property File $icon
 * @property string $text
 * @property-read Combo $combo
 */
class ComboInfo extends Model implements TranslatableContract
{
    use Translatable;

    public $timestamps = false;

    protected $fillable = [
        'combo_id',
        'sort',
        'icon',
    ];

    protected $translatedAttributes = [
        'text',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'icon' => FileCast::class,
        ];
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }
}
