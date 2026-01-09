<?php

namespace App\Share\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tour extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return \Database\Factories\TourFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'link',
        'creator_id',
        'updater_id',
    ];

    /**
     * Get the creator admin.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'creator_id');
    }

    /**
     * Get the updater admin.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updater_id');
    }
}
