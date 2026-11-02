<?php

declare(strict_types=1);

namespace App\Share\Enums;

class OrderStatus extends Enum
{
    public const PendingTerms = 'pending_terms';

    public const Pending = 'pending';

    public const Success = 'success';

    public const Cancelled = 'cancelled';
}
