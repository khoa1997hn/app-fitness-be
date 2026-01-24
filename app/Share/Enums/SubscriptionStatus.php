<?php

declare(strict_types=1);

namespace App\Share\Enums;

class SubscriptionStatus extends Enum
{
    public const Trial = 'trial';

    public const Active = 'active';

    public const Expired = 'expired';

    public const Cancelled = 'cancelled';

    public const GracePeriod = 'grace_period';

    public const Refunded = 'refunded';
}
