<?php

namespace App\Share\Models;

use App\Share\Enums\BillingCycle;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionProvider;
use App\Share\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property Plan $plan
 * @property SubscriptionProvider $provider
 * @property SubscriptionStatus $status
 * @property string|null $provider_subscription_id
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property \Carbon\Carbon|null $grace_period_ends_at
 * @property bool $auto_renew
 * @property float $amount
 * @property string $currency
 * @property BillingCycle $billing_cycle
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read GoogleSubscription|null $googleSubscription
 * @property-read AppleSubscription|null $appleSubscription
 */
class Subscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'plan',
        'provider',
        'status',
        'provider_subscription_id',
        'trial_ends_at',
        'expires_at',
        'cancelled_at',
        'grace_period_ends_at',
        'auto_renew',
        'amount',
        'currency',
        'billing_cycle',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'provider' => SubscriptionProvider::class,
            'status' => SubscriptionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'trial_ends_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'grace_period_ends_at' => 'datetime',
            'auto_renew' => 'boolean',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the google subscription details.
     */
    public function googleSubscription(): HasOne
    {
        return $this->hasOne(GoogleSubscription::class);
    }

    /**
     * Get the apple subscription details.
     */
    public function appleSubscription(): HasOne
    {
        return $this->hasOne(AppleSubscription::class);
    }

    /**
     * @return HasMany<SubscriptionProgramSelection>
     */
    public function programSelections(): HasMany
    {
        return $this->hasMany(SubscriptionProgramSelection::class);
    }
}
