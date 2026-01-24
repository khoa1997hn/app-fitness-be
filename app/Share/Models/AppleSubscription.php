<?php

namespace App\Share\Models;

use App\Share\Enums\AppleSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $user_id
 * @property string $original_transaction_id
 * @property string $transaction_id
 * @property string $product_id
 * @property \Carbon\Carbon|null $purchase_date
 * @property \Carbon\Carbon|null $expires_date
 * @property array|null $raw_response
 * @property AppleSubscriptionStatus|null $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read User $user
 */
class AppleSubscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'user_id',
        'original_transaction_id',
        'transaction_id',
        'product_id',
        'purchase_date',
        'expires_date',
        'raw_response',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'datetime',
            'expires_date' => 'datetime',
            'raw_response' => 'array',
            'status' => AppleSubscriptionStatus::class,
        ];
    }

    /**
     * Get the subscription that owns the apple subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user that owns the apple subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
