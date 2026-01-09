<?php

declare(strict_types=1);

namespace App\Share\Enums;

final class DiscountType extends Enum
{
    const Percentage = 'percentage';

    const FixedAmount = 'fixed_amount';
}
