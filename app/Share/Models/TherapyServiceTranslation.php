<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;

class TherapyServiceTranslation extends Model
{
    protected $fillable = [
        'name',
        'long_description',
        'benefits',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
        ];
    }
}
