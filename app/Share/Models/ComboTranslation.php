<?php

namespace App\Share\Models;

use App\Share\Casts\FileCast;
use Illuminate\Database\Eloquent\Model;

class ComboTranslation extends Model
{
    protected $fillable = [
        'name',
        'cover',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'cover' => FileCast::class,
        ];
    }
}
