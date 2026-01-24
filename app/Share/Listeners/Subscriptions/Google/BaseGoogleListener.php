<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Google;

use App\Share\Models\GoogleSubscription;
use App\Share\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Imdhemy\Purchases\Contracts\PurchaseEventContract;
use Psr\Log\LoggerInterface;

abstract class BaseGoogleListener
{
    protected LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel('subscription_webhook');
    }

    /**
     * Get subscription from event
     */
    protected function getSubscriptionFromEvent(PurchaseEventContract $event): ?Subscription
    {
        try {
            $subscription = $event->getSubscription();
            $purchaseToken = $subscription->getUniqueIdentifier();

            $this->logger->debug('[Google] Looking up subscription by purchase_token', [
                'purchase_token' => $purchaseToken,
                'event_type' => $event->getServerNotification()->getType(),
            ]);

            $googleSubscription = GoogleSubscription::query()
                ->where('purchase_token', $purchaseToken)
                ->first();

            if (! $googleSubscription) {
                $this->logger->warning('[Google] Subscription not found in database', [
                    'purchase_token' => $purchaseToken,
                    'event_type' => $event->getServerNotification()->getType(),
                    'item_id' => $subscription->getItemId(),
                ]);

                return null;
            }

            $this->logger->debug('[Google] Subscription found', [
                'subscription_id' => $googleSubscription->subscription_id,
                'user_id' => $googleSubscription->user_id,
                'purchase_token' => $purchaseToken,
            ]);

            return $googleSubscription->subscription;
        } catch (\Throwable $e) {
            $this->logger->error('[Google] Error getting subscription from event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_type' => $event->getServerNotification()->getType(),
            ]);

            return null;
        }
    }

    /**
     * Check if order is already processed
     */
    protected function isOrderProcessed(string $orderId): bool
    {
        return GoogleSubscription::query()
            ->where('order_id', $orderId)
            ->exists();
    }
}
