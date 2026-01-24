<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Google;

use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionExpired;

class SubscriptionExpiredListener extends BaseGoogleListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionExpired $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $purchaseToken = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Google] SubscriptionExpired event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'purchase_token' => $purchaseToken,
            'item_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Google] SubscriptionExpired: Cannot process event - subscription not found', [
                    'purchase_token' => $purchaseToken,
                ]);

                return;
            }

            $this->logger->debug('[Google] SubscriptionExpired: Processing subscription expiration', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ]);

            // Gọi service expire - service sẽ tự check idempotent
            $expiredSubscription = $this->subscriptionService->expire($subscription);

            $this->logger->info('[Google] SubscriptionExpired event processed successfully', [
                'subscription_id' => $expiredSubscription->id,
                'user_id' => $expiredSubscription->user_id,
                'new_status' => $expiredSubscription->status->value,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error handling SubscriptionExpired event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_token' => $purchaseToken,
            ]);

            throw $e;
        }
    }
}
