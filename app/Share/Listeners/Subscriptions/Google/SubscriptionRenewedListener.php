<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Google;

use App\Share\Enums\GoogleSubscriptionStatus;
use App\Share\Services\Subscription\GoogleService;
use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRenewed;

class SubscriptionRenewedListener extends BaseGoogleListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected GoogleService $googleService
    ) {
        parent::__construct();
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionRenewed $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $purchaseToken = $subscriptionContract->getUniqueIdentifier();
        $orderId = $subscriptionContract->getProviderRepresentation()->getOrderId();

        $this->logger->info('[Google] SubscriptionRenewed event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'purchase_token' => $purchaseToken,
            'order_id' => $orderId,
            'item_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Google] SubscriptionRenewed: Cannot process event - subscription not found', [
                    'purchase_token' => $purchaseToken,
                    'order_id' => $orderId,
                ]);

                return;
            }

            $this->logger->debug('[Google] SubscriptionRenewed: Processing subscription renewal', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'current_expires_at' => $subscription->expires_at?->toIso8601String(),
            ]);

            // Check duplicate order record
            if ($orderId && $this->isOrderProcessed($orderId)) {
                $this->logger->info('[Google] SubscriptionRenewed: Order already processed, skipping', [
                    'subscription_id' => $subscription->id,
                    'order_id' => $orderId,
                    'purchase_token' => $purchaseToken,
                ]);

                return;
            }

            $expiryTime = $subscriptionContract->getExpiryTime();
            $expiresAt = $expiryTime->toCarbon();

            $this->logger->debug('[Google] SubscriptionRenewed: Renewing subscription', [
                'subscription_id' => $subscription->id,
                'new_expires_at' => $expiresAt->toIso8601String(),
            ]);

            // Gọi service renew - service sẽ tự check idempotent
            $renewedSubscription = $this->subscriptionService->renew($subscription, $expiresAt);

            $this->logger->debug('[Google] SubscriptionRenewed: Subscription renewed, updating GoogleSubscription record', [
                'subscription_id' => $renewedSubscription->id,
                'new_status' => $renewedSubscription->status->value,
                'new_expires_at' => $renewedSubscription->expires_at?->toIso8601String(),
            ]);

            // Update GoogleSubscription với thông tin mới từ event
            DB::transaction(function () use ($event, $subscription, $subscriptionContract, $purchaseToken, $orderId, $expiresAt) {
                $itemId = $subscriptionContract->getItemId();
                $subscriptionPurchase = $subscriptionContract->getProviderRepresentation();
                $startTime = $subscriptionPurchase->getStartTime();
                $startTimeCarbon = $startTime?->carbon;

                $this->googleService->createOrUpdateSubscription(
                    $subscription,
                    $purchaseToken,
                    $itemId,
                    $orderId,
                    $startTimeCarbon,
                    $expiresAt,
                    $event->getServerNotification()->getPayload(),
                    GoogleSubscriptionStatus::Active
                );
            });

            $this->logger->info('[Google] SubscriptionRenewed event processed successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'order_id' => $orderId,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error handling SubscriptionRenewed event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_token' => $purchaseToken,
                'order_id' => $orderId,
            ]);

            throw $e;
        }
    }
}
