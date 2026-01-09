<?php

declare(strict_types=1);

namespace App\Share\Enums;

final class EVisaProcessingTime extends Enum
{
    const Normal = 'normal';

    const Urgent = 'urgent';

    const SuperUrgent = 'super_urgent';

    const Express = 'express';

    const Emergency = 'emergency';

    const WeekendOrHoliday = 'weekend_or_holiday';
}
