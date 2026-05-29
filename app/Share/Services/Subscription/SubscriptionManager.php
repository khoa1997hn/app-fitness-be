<?php

namespace App\Share\Services\Subscription;

use App\Share\Enums\SubscriptionProvider;
use App\Share\Models\Subscription;

class SubscriptionManager
{
    public function __construct(private readonly GoogleService $googleService) {}

    /**
     * Cancel subscription on the provider side.
     *
     * Google Play: calls purchases.subscriptions.cancel API (stops auto-renewal;
     * DB update deferred to the SubscriptionCanceled webhook).
     * Apple: not supported by Imdhemy — logs a skip, user must cancel via App Store.
     *
     * @throws \RuntimeException|\GuzzleHttp\Exception\GuzzleException when Google cancel fails
     */
    public function cancelProvider(Subscription $subscription): void
    {
        $provider = $subscription->provider;

        if ($provider->is(SubscriptionProvider::GoogleIap)) {
            $this->googleService->cancelSubscription($subscription);

            return;
        }

        // Apple: outbound cancel not supported by Imdhemy — no-op.
    }
}
