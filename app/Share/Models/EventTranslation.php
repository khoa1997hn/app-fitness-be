<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;

class EventTranslation extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public $timestamps = false;
}
