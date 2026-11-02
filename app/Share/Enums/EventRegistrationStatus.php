<?php

declare(strict_types=1);

namespace App\Share\Enums;

class EventRegistrationStatus extends Enum
{
    public const Pending = 'pending';

    public const Paid = 'paid';

    public const Cancelled = 'cancelled';
}
