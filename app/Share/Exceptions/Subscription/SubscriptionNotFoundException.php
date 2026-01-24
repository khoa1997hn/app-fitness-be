<?php

declare(strict_types=1);

namespace App\Share\Exceptions\Subscription;

class SubscriptionNotFoundException extends SubscriptionException
{
    protected $message = 'Subscription not found.';
}
