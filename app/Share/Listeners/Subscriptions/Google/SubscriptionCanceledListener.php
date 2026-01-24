<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Google;

use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionCanceled;

class SubscriptionCanceledListener extends BaseGoogleListener implements ShouldQueue
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
    public function handle(SubscriptionCanceled $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $purchaseToken = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Google] SubscriptionCanceled event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'purchase_token' => $purchaseToken,
            'item_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Google] SubscriptionCanceled: Cannot process event - subscription not found', [
                    'purchase_token' => $purchaseToken,
                ]);

                return;
            }

            $this->logger->debug('[Google] SubscriptionCanceled: Processing subscription cancellation', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
            ]);

            // Gọi service cancel - service sẽ tự check idempotent
            $cancelledSubscription = $this->subscriptionService->cancel($subscription);

            $this->logger->info('[Google] SubscriptionCanceled event processed successfully', [
                'subscription_id' => $cancelledSubscription->id,
                'user_id' => $cancelledSubscription->user_id,
                'new_status' => $cancelledSubscription->status->value,
                'cancelled_at' => $cancelledSubscription->cancelled_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error handling SubscriptionCanceled event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_token' => $purchaseToken,
            ]);

            throw $e;
        }
    }
}
