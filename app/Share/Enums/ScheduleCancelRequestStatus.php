<?php

declare(strict_types=1);

namespace App\Share\Enums;

class ScheduleCancelRequestStatus extends Enum
{
    public const Pending = 'pending';

    public const Approved = 'approved';

    public const Rejected = 'rejected';
}
