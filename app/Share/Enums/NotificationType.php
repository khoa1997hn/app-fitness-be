<?php

declare(strict_types=1);

namespace App\Share\Enums;

class NotificationType extends Enum
{
    public const Payment = 'payment';

    public const Schedule = 'schedule';

    public const Package = 'package';

    public const Event = 'event';
}
