<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Google;

use App\Share\Enums\SubscriptionStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionPurchased;

class SubscriptionPurchasedListener extends BaseGoogleListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(SubscriptionPurchased $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $purchaseToken = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Google] SubscriptionPurchased event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'purchase_token' => $purchaseToken,
            'item_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Google] SubscriptionPurchased: Cannot process event - subscription not found', [
                    'purchase_token' => $purchaseToken,
                ]);

                return;
            }

            $this->logger->debug('[Google] SubscriptionPurchased: Processing subscription', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'plan' => $subscription->plan->value,
            ]);

            // Đảm bảo subscription status là Active hoặc Trial
            // Service sẽ tự check idempotent nếu cần activate
            if (! $subscription->status->in([SubscriptionStatus::Active, SubscriptionStatus::Trial])) {
                $this->logger->info('[Google] SubscriptionPurchased: Subscription status is not Active or Trial, skipping', [
                    'subscription_id' => $subscription->id,
                    'current_status' => $subscription->status->value,
                    'expected_status' => ['active', 'trial'],
                ]);
            } else {
                $this->logger->debug('[Google] SubscriptionPurchased: Subscription status is valid', [
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status->value,
                ]);
            }

            $this->logger->info('[Google] SubscriptionPurchased event processed successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error handling SubscriptionPurchased event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_token' => $purchaseToken,
            ]);

            throw $e;
        }
    }
}
