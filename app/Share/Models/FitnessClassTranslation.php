<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Model;

class FitnessClassTranslation extends Model
{
    protected $fillable = [
        'description',
    ];

    public $timestamps = false;
}
