<?php

namespace App\Share\Models;

use App\Share\Attributes\File;
use App\Share\Casts\FileCast;
use Illuminate\Database\Eloquent\Model;

/**
 * @property File|null $image
 */
class EventTranslation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'image',
    ];

    protected function casts(): array
    {
        return ['image' => FileCast::class];
    }

    public $timestamps = false;
}
