<?php

namespace App\Share\Services\Subscription;

use App\Share\Enums\BillingCycle;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Exceptions\Subscription\UserHasNoSubscriptionPlanException;
use App\Share\Models\Subscription;
use App\Share\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Activate subscription for user
     */
    public function activate(
        User $user,
        string $provider,
        string $providerSubscriptionId,
        float $amount,
        ?Carbon $expiresAt = null,
        bool $autoRenew = true,
        ?array $metadata = null
    ): Subscription {
        // Get plan from existing subscription
        $existingSubscription = $user->subscription;
        if (! $existingSubscription) {
            throw new UserHasNoSubscriptionPlanException;
        }

        $plan = $existingSubscription->plan;

        return DB::transaction(function () use (
            $user,
            $plan,
            $provider,
            $providerSubscriptionId,
            $expiresAt,
            $autoRenew,
            $amount,
            $metadata
        ) {
            // Cancel existing active subscription if any
            $this->cancelActiveSubscription($user);

            $subscription = Subscription::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan' => $plan,
                    'provider' => $provider,
                    'status' => SubscriptionStatus::Active,
                    'provider_subscription_id' => $providerSubscriptionId,
                    'expires_at' => $expiresAt,
                    'auto_renew' => $autoRenew,
                    'amount' => $amount,
                    'currency' => config('app_payment.currency'),
                    'billing_cycle' => BillingCycle::Monthly,
                    'metadata' => $metadata,
                ]
            );

            // Update user subscription status and plan
            $user->update([
                'subscription_status' => SubscriptionStatus::Active,
                'plan' => $plan,
            ]);

            return $subscription;
        });
    }

    /**
     * Renew subscription
     */
    public function renew(Subscription $subscription, ?Carbon $expiresAt = null): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'expires_at' => $expiresAt,
            'auto_renew' => true,
        ]);

        // Update user subscription status and plan
        $subscription->user->update([
            'subscription_status' => SubscriptionStatus::Active,
            'plan' => $subscription->plan,
        ]);

        return $subscription->fresh();
    }

    /**
     * Cancel subscription
     */
    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);

        // Update user subscription status and plan
        $subscription->user->update([
            'subscription_status' => SubscriptionStatus::Cancelled,
            'plan' => $subscription->plan,
        ]);

        return $subscription->fresh();
    }

    /**
     * Expire subscription
     */
    public function expire(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Expired,
        ]);

        // Update user subscription status and plan
        $subscription->user->update([
            'subscription_status' => SubscriptionStatus::Expired,
            'plan' => $subscription->plan,
        ]);

        return $subscription->fresh();
    }

    /**
     * Cancel active subscription for user
     */
    public function cancelActiveSubscription(User $user): void
    {
        $activeSubscription = $user->validSubscription;

        if ($activeSubscription) {
            $this->cancel($activeSubscription);
        }
    }
}
