<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Google;

use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionRevoked;

class SubscriptionRevokedListener extends BaseGoogleListener implements ShouldQueue
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
    public function handle(SubscriptionRevoked $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $purchaseToken = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Google] SubscriptionRevoked event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'purchase_token' => $purchaseToken,
            'item_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Google] SubscriptionRevoked: Cannot process event - subscription not found', [
                    'purchase_token' => $purchaseToken,
                ]);

                return;
            }

            $this->logger->debug('[Google] SubscriptionRevoked: Processing subscription refund', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'plan' => $subscription->plan->value,
                'amount' => $subscription->amount,
            ]);

            // Gọi service refund - service sẽ tự check idempotent
            $refundedSubscription = $this->subscriptionService->refund($subscription);

            $this->logger->info('[Google] SubscriptionRevoked event processed successfully', [
                'subscription_id' => $refundedSubscription->id,
                'user_id' => $refundedSubscription->user_id,
                'new_status' => $refundedSubscription->status->value,
                'cancelled_at' => $refundedSubscription->cancelled_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error handling SubscriptionRevoked event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_token' => $purchaseToken,
            ]);

            throw $e;
        }
    }
}
