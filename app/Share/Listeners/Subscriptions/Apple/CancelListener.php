<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Apple;

use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\AppStore\Cancel;

class CancelListener extends BaseAppleListener implements ShouldQueue
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
    public function handle(Cancel $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $originalTransactionId = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Apple] Cancel event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'original_transaction_id' => $originalTransactionId,
            'product_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Apple] Cancel: Cannot process event - subscription not found', [
                    'original_transaction_id' => $originalTransactionId,
                ]);

                return;
            }

            $this->logger->debug('[Apple] Cancel: Processing subscription cancellation', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
            ]);

            // Gọi service cancel - service sẽ tự check idempotent
            $cancelledSubscription = $this->subscriptionService->cancel($subscription);

            $this->logger->info('[Apple] Cancel event processed successfully', [
                'subscription_id' => $cancelledSubscription->id,
                'user_id' => $cancelledSubscription->user_id,
                'new_status' => $cancelledSubscription->status->value,
                'cancelled_at' => $cancelledSubscription->cancelled_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error handling Cancel event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_transaction_id' => $originalTransactionId,
            ]);

            throw $e;
        }
    }
}
