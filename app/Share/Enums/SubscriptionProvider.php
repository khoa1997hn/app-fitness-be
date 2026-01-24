<?php

declare(strict_types=1);

namespace App\Share\Enums;

class SubscriptionProvider extends Enum
{
    public const GoogleIap = 'google_iap';

    public const AppleIap = 'apple_iap';
}
