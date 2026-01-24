<?php

namespace App\Share\Services\Subscription;

use App\Share\Enums\AppleSubscriptionStatus;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionProvider;
use App\Share\Exceptions\Subscription\InvalidReceiptException;
use App\Share\Models\AppleSubscription;
use App\Share\Models\Subscription;
use App\Share\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Imdhemy\AppStore\Exceptions\InvalidReceiptException as ImdhemyInvalidReceiptException;
use Imdhemy\Purchases\Facades\Subscription as ImdhemySubscription;
use Psr\Log\LoggerInterface;

class AppleService
{
    protected LoggerInterface $logger;

    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected TrialService $trialService
    ) {
        $this->logger = Log::channel('subscription');
    }

    /**
     * Verify receipt from Apple App Store
     */
    public function verifyReceipt(string $receiptData, User $user): Subscription
    {
        try {
            // Use verifyRenewable() for auto-renewable subscriptions
            $response = ImdhemySubscription::appStore()
                ->receiptData($receiptData)
                ->verifyRenewable();

            // Get the receipt status and check if valid
            $receiptStatus = $response->getStatus();
            if (! $receiptStatus->isValid()) {
                $this->logger->warning('[Apple] Receipt is invalid', [
                    'user_id' => $user->id,
                    'status' => $receiptStatus->getValue(),
                ]);

                throw new \RuntimeException('Receipt is invalid');
            }

            $latestReceiptInfo = $response->getLatestReceiptInfo();
            if (empty($latestReceiptInfo)) {
                throw new \RuntimeException('No subscription found in receipt');
            }

            $receipt = $latestReceiptInfo[0];
            $productId = $receipt->getProductId();
            $originalTransactionId = $receipt->getOriginalTransactionId();
            $transactionId = $receipt->getTransactionId();

            $plan = $this->getPlanFromProductId($productId);
            if (! $plan) {
                throw new \RuntimeException("Invalid product ID: {$productId}");
            }

            $planConfig = config("app_payment.plans.{$plan->value}", []);
            $amount = (float) ($planConfig['price'] ?? 0);
            $expiresDate = $receipt->getExpiresDate();
            $expiresAt = $expiresDate?->toCarbon();

            // Check if this is a trial period from receipt
            $isTrialPeriod = $receipt->getIsTrialPeriod() === true;

            // If trial period but no expires date, throw error
            if ($isTrialPeriod && ! $expiresAt) {
                throw new \RuntimeException('Expires date is required for trial period');
            }

            // Use DB transaction for all database operations
            return DB::transaction(function () use (
                $user,
                $plan,
                $isTrialPeriod,
                $originalTransactionId,
                $amount,
                $expiresAt,
                $receipt,
                $response,
                $productId,
                $transactionId
            ) {
                if ($isTrialPeriod) {
                    // Start trial if receipt indicates trial period
                    $subscriptionModel = $this->trialService->startTrial(
                        user: $user,
                        plan: $plan,
                        provider: SubscriptionProvider::AppleIap,
                        providerSubscriptionId: $originalTransactionId,
                        amount: $amount,
                        expiresAt: $expiresAt
                    );
                } else {
                    // Activate subscription (renew or upgrade)
                    $subscriptionModel = $this->subscriptionService->activate(
                        user: $user,
                        provider: SubscriptionProvider::AppleIap,
                        providerSubscriptionId: $originalTransactionId,
                        amount: $amount,
                        expiresAt: $expiresAt
                    );
                }

                // Create or update Apple subscription record
                $this->createOrUpdateSubscription(
                    $subscriptionModel,
                    $originalTransactionId,
                    $transactionId,
                    $productId,
                    $receipt->getPurchaseDate()?->toCarbon(),
                    $expiresAt,
                    $response->toArray(),
                    AppleSubscriptionStatus::Active
                );

                return $subscriptionModel->load('appleSubscription');
            });
        } catch (ImdhemyInvalidReceiptException $e) {
            $this->logger->error('[Apple] Invalid receipt', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            throw new InvalidReceiptException('Invalid receipt: '.$e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error during receipt verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            throw $e;
        }
    }

    /**
     * Create or update Apple subscription record
     */
    public function createOrUpdateSubscription(
        Subscription $subscription,
        string $originalTransactionId,
        string $transactionId,
        string $productId,
        ?Carbon $purchaseDate = null,
        ?Carbon $expiresDate = null,
        ?array $rawResponse = null,
        ?string $status = null
    ): AppleSubscription {
        return AppleSubscription::query()->updateOrCreate(
            [
                'subscription_id' => $subscription->id,
                'transaction_id' => $transactionId,
            ],
            [
                'user_id' => $subscription->user_id,
                'original_transaction_id' => $originalTransactionId,
                'product_id' => $productId,
                'purchase_date' => $purchaseDate,
                'expires_date' => $expiresDate,
                'raw_response' => $rawResponse,
                'status' => $status,
            ]
        );
    }

    /**
     * Get plan from product ID
     */
    protected function getPlanFromProductId(string $productId): ?Plan
    {
        $plans = config('app_payment.plans', []);

        foreach ($plans as $planKey => $planConfig) {
            if (isset($planConfig['apple_product_id']) && $planConfig['apple_product_id'] === $productId) {
                return Plan::fromValue(strtolower($planKey));
            }
        }

        return null;
    }
}
