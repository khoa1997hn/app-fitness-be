<?php

declare(strict_types=1);

namespace App\Share\Exceptions\Subscription;

class UserHasNoSubscriptionPlanException extends SubscriptionException
{
    public function __construct()
    {
        parent::__construct('User does not have a subscription plan. Please start trial first.');
    }
}
