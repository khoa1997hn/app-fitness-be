<?php

namespace App\Share\Models;

use App\Share\Casts\FileCast;
use Illuminate\Database\Eloquent\Model;

class BannerTranslation extends Model
{
    protected $fillable = [
        'image',
        'link_url',
        'order',
        'is_active',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
            'image' => FileCast::class,
        ];
    }
}
