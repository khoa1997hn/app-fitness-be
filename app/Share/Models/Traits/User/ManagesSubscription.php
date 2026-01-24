<?php

namespace App\Share\Models\Traits\User;

use App\Share\Enums\SubscriptionStatus;
use App\Share\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait ManagesSubscription
{
    /**
     * Get the user's subscription.
     *
     * @return HasOne<Subscription>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get the user's valid subscription (trial, active, grace period).
     *
     * @return HasOne<Subscription>
     */
    public function validSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->whereIn('status', [
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
            SubscriptionStatus::GracePeriod,
        ]);
    }
}
