<?php

namespace App\Share\Services\Subscription;

use App\Share\Enums\GoogleSubscriptionStatus;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionProvider;
use App\Share\Models\GoogleSubscription;
use App\Share\Models\Subscription;
use App\Share\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Imdhemy\GooglePlay\Subscriptions\SubscriptionPurchase;
use Imdhemy\Purchases\Facades\Subscription as ImdhemySubscription;
use Psr\Log\LoggerInterface;

class GoogleService
{
    protected LoggerInterface $logger;

    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected TrialService $trialService
    ) {
        $this->logger = Log::channel('subscription');
    }

    /**
     * Verify purchase token from Google Play
     */
    public function verifyPurchase(string $purchaseToken, string $itemId, User $user): Subscription
    {
        try {
            $subscription = ImdhemySubscription::googlePlay()
                ->id($itemId)
                ->token($purchaseToken)
                ->get();

            $plan = $this->getPlanFromItemId($itemId);
            if (! $plan) {
                throw new \RuntimeException("Invalid item ID: {$itemId}");
            }

            $planConfig = config("app_payment.plans.{$plan->value}", []);
            $amount = (float) ($planConfig['price'] ?? 0);
            $expiresAt = $subscription->getExpiryTime()?->carbon;

            // Check if this is a trial period from receipt
            $isTrialPeriod = $subscription->getPaymentState() === SubscriptionPurchase::PAYMENT_STATE_FREE_TRIAL;

            // If trial period but no expires date, throw error
            if ($isTrialPeriod && ! $expiresAt) {
                throw new \RuntimeException('Expires date is required for trial period');
            }

            // Use DB transaction for all database operations
            return DB::transaction(function () use (
                $user,
                $plan,
                $isTrialPeriod,
                $purchaseToken,
                $subscription,
                $amount,
                $expiresAt,
                $itemId
            ) {
                if ($isTrialPeriod) {
                    // Start trial if receipt indicates trial period
                    $subscriptionModel = $this->trialService->startTrial(
                        user: $user,
                        plan: $plan,
                        provider: SubscriptionProvider::GoogleIap,
                        providerSubscriptionId: $subscription->getOrderId() ?? $purchaseToken,
                        amount: $amount,
                        expiresAt: $expiresAt
                    );
                } else {
                    // Activate subscription (renew or upgrade)
                    $subscriptionModel = $this->subscriptionService->activate(
                        user: $user,
                        plan: $plan,
                        provider: SubscriptionProvider::GoogleIap,
                        providerSubscriptionId: $subscription->getOrderId() ?? $purchaseToken,
                        amount: $amount,
                        expiresAt: $expiresAt
                    );
                }

                // Create or update Google subscription record
                $this->createOrUpdateSubscription(
                    $subscriptionModel,
                    $purchaseToken,
                    $itemId,
                    $subscription->getOrderId(),
                    $subscription->getStartTime()?->carbon,
                    $expiresAt,
                    $subscription->toArray(),
                    $subscription->getAutoRenewing() ? GoogleSubscriptionStatus::Active : GoogleSubscriptionStatus::Cancelled
                );

                return $subscriptionModel->load('googleSubscription');
            });
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error during purchase verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_token' => $purchaseToken,
                'item_id' => $itemId,
                'user_id' => $user->id,
            ]);

            throw $e;
        }
    }

    /**
     * Create or update Google subscription record
     */
    public function createOrUpdateSubscription(
        Subscription $subscription,
        string $purchaseToken,
        string $itemId,
        ?string $orderId = null,
        ?Carbon $transactionDate = null,
        ?Carbon $expiryDate = null,
        ?array $rawResponse = null,
        ?string $status = null
    ): GoogleSubscription {
        return GoogleSubscription::query()->updateOrCreate(
            [
                'subscription_id' => $subscription->id,
                'purchase_token' => $purchaseToken,
            ],
            [
                'user_id' => $subscription->user_id,
                'order_id' => $orderId,
                'item_id' => $itemId,
                'transaction_date' => $transactionDate,
                'expiry_date' => $expiryDate,
                'raw_response' => $rawResponse,
                'status' => $status,
            ]
        );
    }

    /**
     * Cancel subscription on Google Play (stops auto-renewal, user retains access until end of billing period).
     * DB status is NOT updated here — wait for the SubscriptionCanceled webhook.
     *
     * @throws \RuntimeException when google subscription record is missing
     * @throws \GuzzleHttp\Exception\GuzzleException when Google Play API call fails
     */
    public function cancelSubscription(Subscription $subscriptionModel): void
    {
        $googleSubscription = $subscriptionModel->googleSubscription;

        if (! $googleSubscription) {
            throw new \RuntimeException(
                '[Google] Cannot cancel: no GoogleSubscription record for subscription '.$subscriptionModel->id
            );
        }

        $this->logger->info('[Google] Cancelling subscription via API', [
            'subscription_id' => $subscriptionModel->id,
            'user_id' => $subscriptionModel->user_id,
            'item_id' => $googleSubscription->item_id,
        ]);

        ImdhemySubscription::googlePlay()
            ->id($googleSubscription->item_id)
            ->token($googleSubscription->purchase_token)
            ->cancel();

        $this->logger->info('[Google] Subscription cancelled via API successfully', [
            'subscription_id' => $subscriptionModel->id,
            'user_id' => $subscriptionModel->user_id,
        ]);
    }

    /**
     * Get plan from item ID
     */
    protected function getPlanFromItemId(string $itemId): ?Plan
    {
        $plans = config('app_payment.plans', []);

        foreach ($plans as $planKey => $planConfig) {
            if (isset($planConfig['google_item_id']) && $planConfig['google_item_id'] === $itemId) {
                return Plan::fromValue(strtolower($planKey));
            }
        }

        return null;
    }
}
