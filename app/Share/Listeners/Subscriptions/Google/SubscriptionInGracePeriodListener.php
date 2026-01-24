<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Google;

use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Imdhemy\Purchases\Events\GooglePlay\SubscriptionInGracePeriod;

class SubscriptionInGracePeriodListener extends BaseGoogleListener implements ShouldQueue
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
    public function handle(SubscriptionInGracePeriod $event): void
    {
        $subscriptionContract = $event->getSubscription();
        $purchaseToken = $subscriptionContract->getUniqueIdentifier();

        $this->logger->info('[Google] SubscriptionInGracePeriod event received', [
            'event_type' => $event->getServerNotification()->getType(),
            'purchase_token' => $purchaseToken,
            'item_id' => $subscriptionContract->getItemId(),
        ]);

        try {
            $subscription = $this->getSubscriptionFromEvent($event);

            if (! $subscription) {
                $this->logger->warning('[Google] SubscriptionInGracePeriod: Cannot process event - subscription not found', [
                    'purchase_token' => $purchaseToken,
                ]);

                return;
            }

            $this->logger->debug('[Google] SubscriptionInGracePeriod: Subscription entered grace period', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'current_status' => $subscription->status->value,
                'expires_at' => $subscription->expires_at?->toIso8601String(),
            ]);

            // Log only, không thay đổi status (grace period sẽ tự expire sau)
            $this->logger->info('[Google] SubscriptionInGracePeriod event processed successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'note' => 'Subscription entered grace period, will expire after grace period ends',
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error handling SubscriptionInGracePeriod event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'purchase_token' => $purchaseToken,
            ]);

            throw $e;
        }
    }
}
