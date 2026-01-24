<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Apple;

use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\AppStore\Expired;

class ExpiredListener extends BaseAppleListener implements ShouldQueue
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
    public function handle(Expired $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $originalTransactionId = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Apple] Expired event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'original_transaction_id' => $originalTransactionId,
            'product_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Apple] Expired: Cannot process event - subscription not found', [
                    'original_transaction_id' => $originalTransactionId,
                ]);

                return;
            }

            $this->logger->debug('[Apple] Expired: Processing subscription expiration', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ]);

            // Gọi service expire - service sẽ tự check idempotent
            $expiredSubscription = $this->subscriptionService->expire($subscription);

            $this->logger->info('[Apple] Expired event processed successfully', [
                'subscription_id' => $expiredSubscription->id,
                'user_id' => $expiredSubscription->user_id,
                'new_status' => $expiredSubscription->status->value,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error handling Expired event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_transaction_id' => $originalTransactionId,
            ]);

            throw $e;
        }
    }
}
