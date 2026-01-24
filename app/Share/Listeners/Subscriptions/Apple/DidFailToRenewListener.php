<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Apple;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\AppStore\DidFailToRenew;

class DidFailToRenewListener extends BaseAppleListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(DidFailToRenew $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $originalTransactionId = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Apple] DidFailToRenew event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'original_transaction_id' => $originalTransactionId,
            'product_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Apple] DidFailToRenew: Cannot process event - subscription not found', [
                    'original_transaction_id' => $originalTransactionId,
                ]);

                return;
            }

            $this->logger->debug('[Apple] DidFailToRenew: Processing failed renewal', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ]);

            // Log warning về failed renewal
            // Không đổi status (vẫn giữ Active để user có thể retry payment)
            $this->logger->warning('[Apple] DidFailToRenew: Subscription renewal failed', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
                'note' => 'Subscription status remains unchanged to allow retry',
            ]);

            $this->logger->info('[Apple] DidFailToRenew event processed successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error handling DidFailToRenew event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_transaction_id' => $originalTransactionId,
            ]);

            throw $e;
        }
    }
}
