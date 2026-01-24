<?php

declare(strict_types=1);

namespace App\Share\Exceptions\Subscription;

class InvalidReceiptException extends SubscriptionException
{
    public function __construct(string $message = 'Receipt is invalid')
    {
        parent::__construct($message);
    }
}
