<?php

declare(strict_types=1);

namespace App\Share\Enums;

class AppleSubscriptionStatus extends Enum
{
    public const Active = 'active';

    public const Cancelled = 'cancelled';

    public const Expired = 'expired';

    public const Pending = 'pending';
}
