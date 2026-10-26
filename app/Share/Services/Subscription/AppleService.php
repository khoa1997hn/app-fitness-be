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
use Imdhemy\AppStore\Jws\AppStoreJwsVerifier;
use Imdhemy\AppStore\Jws\Parser;
use Imdhemy\AppStore\ValueObjects\JwsTransactionInfo;
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
     * Verify StoreKit 2 signed transaction (JWS) from the client.
     */
    public function verifyTransaction(string $signedTransaction, User $user): Subscription
    {
        try {
            $transactionInfo = $this->decodeSignedTransaction($signedTransaction, $user);

            $bundleId = config('liap.appstore_bundle_id');
            if (! $bundleId || $transactionInfo->getBundleId() !== $bundleId) {
                throw new \RuntimeException('Bundle ID mismatch');
            }

            if ($transactionInfo->getRevocationDate() !== null) {
                throw new \RuntimeException('Transaction has been revoked');
            }

            if ($transactionInfo->getType() !== JwsTransactionInfo::TYPE_AUTO_RENEWABLE) {
                throw new \RuntimeException('Transaction is not an auto-renewable subscription');
            }

            $productId = $transactionInfo->getProductId();
            $originalTransactionId = $transactionInfo->getOriginalTransactionId();
            $transactionId = $transactionInfo->getTransactionId();

            if (! $productId || ! $originalTransactionId || ! $transactionId) {
                throw new \RuntimeException('Missing required transaction fields');
            }

            $plan = $this->getPlanFromProductId($productId);
            if (! $plan) {
                throw new \RuntimeException("Invalid product ID: {$productId}");
            }

            $planConfig = config("app_payment.plans.{$plan->value}", []);
            $amount = (float) ($planConfig['price'] ?? 0);
            $expiresAt = $transactionInfo->getExpiresDate()?->toCarbon();
            $isTrialPeriod = $this->isFreeTrialTransaction($transactionInfo);

            if ($isTrialPeriod && ! $expiresAt) {
                throw new \RuntimeException('Expires date is required for trial period');
            }

            $rawResponse = $transactionInfo->getClaims();

            return DB::transaction(function () use (
                $user,
                $plan,
                $isTrialPeriod,
                $originalTransactionId,
                $amount,
                $expiresAt,
                $transactionInfo,
                $productId,
                $transactionId,
                $rawResponse
            ) {
                if ($isTrialPeriod) {
                    $subscriptionModel = $this->trialService->startTrial(
                        user: $user,
                        plan: $plan,
                        provider: SubscriptionProvider::AppleIap,
                        providerSubscriptionId: $originalTransactionId,
                        amount: $amount,
                        expiresAt: $expiresAt
                    );
                } else {
                    $subscriptionModel = $this->subscriptionService->activate(
                        user: $user,
                        plan: $plan,
                        provider: SubscriptionProvider::AppleIap,
                        providerSubscriptionId: $originalTransactionId,
                        amount: $amount,
                        expiresAt: $expiresAt
                    );
                }

                $this->createOrUpdateSubscription(
                    $subscriptionModel,
                    $originalTransactionId,
                    $transactionId,
                    $productId,
                    $transactionInfo->getPurchaseDate()?->toCarbon(),
                    $expiresAt,
                    $rawResponse,
                    AppleSubscriptionStatus::Active
                );

                return $subscriptionModel->load('appleSubscription');
            });
        } catch (InvalidReceiptException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error during transaction verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            throw $e;
        }
    }

    protected function decodeSignedTransaction(string $signedTransaction, User $user): JwsTransactionInfo
    {
        $signedTransaction = trim($signedTransaction);

        if ($signedTransaction === '') {
            throw new InvalidReceiptException('Invalid transaction: empty payload');
        }

        try {
            $jws = Parser::toJws($signedTransaction);
            $verifier = new AppStoreJwsVerifier;

            if (! $verifier->verify($jws)) {
                throw new InvalidReceiptException('Invalid transaction: signature verification failed');
            }

            return new JwsTransactionInfo($jws);
        } catch (InvalidReceiptException $e) {
            $this->logger->error('[Apple] Invalid transaction', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Invalid transaction', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            throw new InvalidReceiptException('Invalid transaction: '.$e->getMessage());
        }
    }

    protected function isFreeTrialTransaction(JwsTransactionInfo $transactionInfo): bool
    {
        $claims = $transactionInfo->getClaims();

        if (($claims['offerDiscountType'] ?? null) === 'FREE_TRIAL') {
            return true;
        }

        return ($claims['isTrialPeriod'] ?? false) === true;
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
