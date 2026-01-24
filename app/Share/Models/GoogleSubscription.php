<?php

namespace App\Share\Models;

use App\Share\Enums\GoogleSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $user_id
 * @property string $purchase_token
 * @property string|null $order_id
 * @property string $item_id
 * @property \Carbon\Carbon|null $transaction_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property array|null $raw_response
 * @property GoogleSubscriptionStatus|null $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Subscription $subscription
 * @property-read User $user
 */
class GoogleSubscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'user_id',
        'purchase_token',
        'order_id',
        'item_id',
        'transaction_date',
        'expiry_date',
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
            'transaction_date' => 'datetime',
            'expiry_date' => 'datetime',
            'raw_response' => 'array',
            'status' => GoogleSubscriptionStatus::class,
        ];
    }

    /**
     * Get the subscription that owns the google subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user that owns the google subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
