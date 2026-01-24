<?php

declare(strict_types=1);

namespace App\Share\Listeners\Subscriptions\Apple;

use App\Share\Models\AppleSubscription;
use App\Share\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Imdhemy\Purchases\Contracts\PurchaseEventContract;
use Psr\Log\LoggerInterface;

abstract class BaseAppleListener
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
            $originalTransactionId = $subscription->getUniqueIdentifier();

            $this->logger->debug('[Apple] Looking up subscription by original_transaction_id', [
                'original_transaction_id' => $originalTransactionId,
                'event_type' => $event->getServerNotification()->getType(),
            ]);

            $appleSubscription = AppleSubscription::query()
                ->where('original_transaction_id', $originalTransactionId)
                ->first();

            if (! $appleSubscription) {
                $this->logger->warning('[Apple] Subscription not found in database', [
                    'original_transaction_id' => $originalTransactionId,
                    'event_type' => $event->getServerNotification()->getType(),
                    'subscription_id_from_event' => $subscription->getItemId(),
                ]);

                return null;
            }

            $this->logger->debug('[Apple] Subscription found', [
                'subscription_id' => $appleSubscription->subscription_id,
                'user_id' => $appleSubscription->user_id,
                'original_transaction_id' => $originalTransactionId,
            ]);

            return $appleSubscription->subscription;
        } catch (\Throwable $e) {
            $this->logger->error('[Apple] Error getting subscription from event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_type' => $event->getServerNotification()->getType(),
            ]);

            return null;
        }
    }

    /**
     * Check if transaction is already processed
     */
    protected function isTransactionProcessed(string $transactionId): bool
    {
        return AppleSubscription::query()
            ->where('transaction_id', $transactionId)
            ->exists();
    }
}
