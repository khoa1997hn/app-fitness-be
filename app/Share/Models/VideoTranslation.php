<?php

namespace App\Share\Models;

use App\Share\Casts\FileCast;
use Illuminate\Database\Eloquent\Model;

class VideoTranslation extends Model
{
    protected $fillable = [
        'file',
        'duration_seconds',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'file' => FileCast::class,
            'duration_seconds' => 'integer',
        ];
    }
}
