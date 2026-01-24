<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Apple;

use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\AppStore\Refund;

class RefundListener extends BaseAppleListener implements ShouldQueue
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
    public function handle(Refund $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $originalTransactionId = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Apple] Refund event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'original_transaction_id' => $originalTransactionId,
            'product_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Apple] Refund: Cannot process event - subscription not found', [
                    'original_transaction_id' => $originalTransactionId,
                ]);

                return;
            }

            $this->logger->debug('[Apple] Refund: Processing subscription refund', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'plan' => $subscription->plan->value,
                'amount' => $subscription->amount,
            ]);

            // Gọi service refund - service sẽ tự check idempotent
            $refundedSubscription = $this->subscriptionService->refund($subscription);

            $this->logger->info('[Apple] Refund event processed successfully', [
                'subscription_id' => $refundedSubscription->id,
                'user_id' => $refundedSubscription->user_id,
                'new_status' => $refundedSubscription->status->value,
                'cancelled_at' => $refundedSubscription->cancelled_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error handling Refund event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_transaction_id' => $originalTransactionId,
            ]);

            throw $e;
        }
    }
}
