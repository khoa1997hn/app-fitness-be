<?php

declare(strict_types=1);

namespace App\Share\Enums;

class PaymentMethod extends Enum
{
    public const Momo = 'momo';

    public const Vnpay = 'vnpay';

    public const Cash = 'cash';
}
