<?php

return [
    /*
    | Thanh toán mock: xác nhận thành công ngay, không qua cổng thật.
    | Mặc định tắt trên production vì mock cấp gói miễn phí.
    */
    'payment_mock_enabled' => (bool) env('GYM_PAYMENT_MOCK_ENABLED', env('APP_ENV', 'production') !== 'production'),

    // Thời hạn sử dụng gói trị liệu sau khi mua (ngày)
    'therapy_validity_days' => (int) env('GYM_THERAPY_VALIDITY_DAYS', 90),
];
