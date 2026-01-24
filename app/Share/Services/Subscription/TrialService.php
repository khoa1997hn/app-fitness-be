<?php

namespace App\Share\Services\Subscription;

use App\Share\Enums\BillingCycle;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Models\Subscription;
use App\Share\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrialService
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Start trial for user
     */
    public function startTrial(
        User $user,
        Plan $plan,
        string $provider,
        string $providerSubscriptionId,
        float $amount,
        Carbon $expiresAt,
        ?array $metadata = null
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $plan,
            $provider,
            $providerSubscriptionId,
            $expiresAt,
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
                    'status' => SubscriptionStatus::Trial,
                    'provider_subscription_id' => $providerSubscriptionId,
                    'trial_ends_at' => $expiresAt,
                    'expires_at' => $expiresAt,
                    'auto_renew' => true,
                    'amount' => $amount,
                    'currency' => config('app_payment.currency'),
                    'billing_cycle' => BillingCycle::Monthly,
                    'metadata' => $metadata,
                ]
            );

            // Update user subscription status and plan
            $user->update([
                'subscription_status' => SubscriptionStatus::Trial,
                'plan' => $plan,
            ]);

            return $subscription;
        });
    }
}
