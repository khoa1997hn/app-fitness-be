<?php

namespace App\Share\Models;

use App\Share\Casts\FileCast;
use Illuminate\Database\Eloquent\Model;

class ProgramTranslation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'cover',
        'sort',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'cover' => FileCast::class,
            'sort' => 'integer',
        ];
    }
}
