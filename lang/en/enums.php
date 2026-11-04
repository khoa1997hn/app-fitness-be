<?php

use App\Share\Enums\PaymentMethod;

return [
    PaymentMethod::class => [
        PaymentMethod::Momo => 'MoMo',
        PaymentMethod::Vnpay => 'VNPay',
        PaymentMethod::Cash => 'Cash',
    ],
];
