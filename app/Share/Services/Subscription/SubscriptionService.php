<?php

namespace App\Share\Services\Subscription;

use App\Share\Enums\BillingCycle;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Exceptions\Subscription\SubscriptionNotFoundException;
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
        Plan $plan,
        string $provider,
        string $providerSubscriptionId,
        float $amount,
        ?Carbon $expiresAt = null,
        bool $autoRenew = true,
        ?array $metadata = null
    ): Subscription {
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
            // Lock subscription để đảm bảo thread-safe
            Subscription::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

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
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Active,
                'expires_at' => $expiresAt,
                'auto_renew' => true,
            ],
            SubscriptionStatus::Active
        );
    }

    /**
     * Cancel subscription
     */
    public function cancel(Subscription $subscription): Subscription
    {
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
                'auto_renew' => false,
            ],
            SubscriptionStatus::Cancelled
        );
    }

    /**
     * Expire subscription
     */
    public function expire(Subscription $subscription): Subscription
    {
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Expired,
            ],
            SubscriptionStatus::Expired
        );
    }

    /**
     * Refund subscription
     */
    public function refund(Subscription $subscription): Subscription
    {
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Refunded,
                'cancelled_at' => now(),
                'auto_renew' => false,
            ],
            SubscriptionStatus::Refunded
        );
    }

    /**
     * Update subscription with lock and update user
     */
    protected function updateSubscription(
        Subscription $subscription,
        array $subscriptionData,
        string $userStatus
    ): Subscription {
        return DB::transaction(function () use ($subscription, $subscriptionData, $userStatus) {
            // Lock subscription để đảm bảo thread-safe
            $subscription = Subscription::query()
                ->where('id', $subscription->id)
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw new SubscriptionNotFoundException;
            }

            // Update subscription theo dữ liệu từ event
            $subscription->update($subscriptionData);

            // Update user subscription status and plan.
            // Use withTrashed() to handle soft-deleted users (e.g., account deletion flow).
            $subscription->user()->withTrashed()->first()?->update([
                'subscription_status' => $userStatus,
                'plan' => $subscription->plan,
            ]);

            return $subscription->fresh();
        });
    }
}
