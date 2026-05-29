<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramGoalTranslation extends Model
{
    protected $fillable = [
        'content',
    ];

    public $timestamps = false;
}
