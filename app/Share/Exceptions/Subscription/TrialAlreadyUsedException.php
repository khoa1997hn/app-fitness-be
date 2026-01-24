<?php

declare(strict_types=1);

namespace App\Share\Exceptions\Subscription;

class TrialAlreadyUsedException extends SubscriptionException
{
    public function __construct()
    {
        parent::__construct('User has already used trial period');
    }
}
