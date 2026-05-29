<?php

namespace App\Share\Models;

use App\Share\Casts\FileCast;
use Illuminate\Database\Eloquent\Model;

class LessonTranslation extends Model
{
    protected $fillable = [
        'name',
        'description',
        'thumbnail',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'thumbnail' => FileCast::class,
        ];
    }
}
