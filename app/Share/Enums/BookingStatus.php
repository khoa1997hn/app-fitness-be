<?php

declare(strict_types=1);

namespace App\Share\Enums;

class BookingStatus extends Enum
{
    public const Confirmed = 'confirmed';

    public const Cancelled = 'cancelled';
}
