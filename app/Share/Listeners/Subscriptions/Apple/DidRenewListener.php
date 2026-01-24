<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Apple;

use App\Share\Enums\AppleSubscriptionStatus;
use App\Share\Services\Subscription\AppleService;
use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Imdhemy\Purchases\Events\AppStore\DidRenew;

class DidRenewListener extends BaseAppleListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected AppleService $appleService
    ) {
        parent::__construct();
    }

    /**
     * Handle the event.
     */
    public function handle(DidRenew $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $transactionId = $subscriptionContract->getProviderRepresentation()->getTransactionId();
        $originalTransactionId = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Apple] DidRenew event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'transaction_id' => $transactionId,
            'original_transaction_id' => $originalTransactionId,
            'product_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Apple] DidRenew: Cannot process event - subscription not found', [
                    'original_transaction_id' => $originalTransactionId,
                    'transaction_id' => $transactionId,
                ]);

                return;
            }

            $this->logger->debug('[Apple] DidRenew: Processing subscription renewal', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'current_expires_at' => $subscription->expires_at?->toIso8601String(),
            ]);

            // Check duplicate transaction record
            if ($this->isTransactionProcessed($transactionId)) {
                $this->logger->info('[Apple] DidRenew: Transaction already processed, skipping', [
                    'subscription_id' => $subscription->id,
                    'transaction_id' => $transactionId,
                    'original_transaction_id' => $originalTransactionId,
                ]);

                return;
            }

            $expiresDate = $subscriptionContract->getProviderRepresentation()->getExpiresDate();
            $expiresAt = $expiresDate ? $expiresDate->toCarbon() : null;

            $this->logger->debug('[Apple] DidRenew: Renewing subscription', [
                'subscription_id' => $subscription->id,
                'new_expires_at' => $expiresAt?->toIso8601String(),
            ]);

            // Gọi service renew - service sẽ tự check idempotent
            $renewedSubscription = $this->subscriptionService->renew($subscription, $expiresAt);

            $this->logger->debug('[Apple] DidRenew: Subscription renewed, updating AppleSubscription record', [
                'subscription_id' => $renewedSubscription->id,
                'new_status' => $renewedSubscription->status->value,
                'new_expires_at' => $renewedSubscription->expires_at?->toIso8601String(),
            ]);

            // Update AppleSubscription với thông tin mới từ event
            DB::transaction(function () use ($event, $subscription, $subscriptionContract, $transactionId, $expiresAt, $originalTransactionId) {
                $productId = $subscriptionContract->getItemId();
                $purchaseDate = $subscriptionContract->getProviderRepresentation()->getPurchaseDate();
                $purchaseDateCarbon = $purchaseDate ? $purchaseDate->toCarbon() : null;

                $this->appleService->createOrUpdateSubscription(
                    $subscription,
                    $originalTransactionId,
                    $transactionId,
                    $productId,
                    $purchaseDateCarbon,
                    $expiresAt,
                    $event->getServerNotification()->getPayload(),
                    AppleSubscriptionStatus::Active
                );
            });

            $this->logger->info('[Apple] DidRenew event processed successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'transaction_id' => $transactionId,
                'expires_at' => $expiresAt?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error handling DidRenew event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'transaction_id' => $transactionId,
                'original_transaction_id' => $originalTransactionId,
            ]);

            throw $e;
        }
    }
}
