<?php

declare(strict_types=1);

namespace App\Share\Enums;

class OrderType extends Enum
{
    public const Package = 'package';

    public const Therapy = 'therapy';

    public const ExtraPeople = 'extra_people';
}
