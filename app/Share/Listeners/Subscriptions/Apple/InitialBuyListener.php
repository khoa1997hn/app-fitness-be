<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Apple;

use App\Share\Enums\SubscriptionStatus;
use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\AppStore\InitialBuy;

class InitialBuyListener extends BaseAppleListener implements ShouldQueue
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
    public function handle(InitialBuy $event): void
    {
        $this->logger->info('[Apple] InitialBuy event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'subscription_id' => $event->getSubscription()->getItemId(),
            'original_transaction_id' => $event->getSubscription()->getUniqueIdentifier(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Apple] InitialBuy: Cannot process event - subscription not found', [
                    'original_transaction_id' => $event->getSubscription()->getUniqueIdentifier(),
                ]);

                return;
            }

            $this->logger->debug('[Apple] InitialBuy: Processing subscription', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'plan' => $subscription->plan->value,
            ]);

            // Đảm bảo subscription status là Active hoặc Trial
            // Service sẽ tự check idempotent nếu cần activate
            if (! $subscription->status->in([SubscriptionStatus::Active, SubscriptionStatus::Trial])) {
                $this->logger->info('[Apple] InitialBuy: Subscription status is not Active or Trial, skipping', [
                    'subscription_id' => $subscription->id,
                    'current_status' => $subscription->status->value,
                    'expected_status' => ['active', 'trial'],
                ]);
            } else {
                $this->logger->debug('[Apple] InitialBuy: Subscription status is valid', [
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status->value,
                ]);
            }

            $this->logger->info('[Apple] InitialBuy event processed successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error handling InitialBuy event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_transaction_id' => $event->getSubscription()->getUniqueIdentifier(),
            ]);

            throw $e;
        }
    }
}
