<?php

declare(strict_types=1);

namespace App\Share\Enums;

class EventStatus extends Enum
{
    public const Open = 'open';

    public const Closed = 'closed';

    public const Cancelled = 'cancelled';
}
