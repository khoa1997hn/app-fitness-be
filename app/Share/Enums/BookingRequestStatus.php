<?php

declare(strict_types=1);

namespace App\Share\Enums;

class BookingRequestStatus extends Enum
{
    public const Pending = 'pending';

    public const Confirmed = 'confirmed';

    public const Rejected = 'rejected';

    public const Cancelled = 'cancelled';

    public const Completed = 'completed';
}
