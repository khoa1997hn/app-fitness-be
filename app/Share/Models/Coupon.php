<?php

namespace App\Share\Models;

use App\Share\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return \Database\Factories\CouponFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'start_at',
        'end_at',
        'total_quantity',
        'used_quantity',
        'discount_type',
        'discount_value',
        'creator_id',
        'updater_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
        ];
    }

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
