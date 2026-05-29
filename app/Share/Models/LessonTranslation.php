<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;

class LessonTranslation extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public $timestamps = false;
}
