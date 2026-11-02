<?php

return [
    /*
    | Thanh toán mock: xác nhận thành công ngay, không qua cổng thật.
    | Mặc định tắt trên production vì mock cấp gói miễn phí.
    */
    'payment_mock_enabled' => (bool) env('GYM_PAYMENT_MOCK_ENABLED', env('APP_ENV', 'production') !== 'production'),

    // Trong vòng từng này tiếng trước giờ học thì học viên không hủy được
    'cancel_lock_hours' => (int) env('GYM_CANCEL_LOCK_HOURS', 8),

    // Gói off-peak chỉ đặt được lớp bắt đầu từ giờ này trở về trước, hoặc từ giờ kia trở đi
    'off_peak_morning_end' => env('GYM_OFF_PEAK_MORNING_END', '08:00'),
    'off_peak_evening_start' => env('GYM_OFF_PEAK_EVENING_START', '23:00'),

    // Giá một suất người tham gia thêm (VNĐ)
    'extra_people_price' => (int) env('GYM_EXTRA_PEOPLE_PRICE', 200000),

    // Nhắc lịch trước giờ tập từng này tiếng
    'reminder_hours' => (int) env('GYM_REMINDER_HOURS', 24),

    // Báo gói sắp hết hạn trước từng này ngày
    'package_expiry_notice_days' => (int) env('GYM_PACKAGE_EXPIRY_NOTICE_DAYS', 7),

    'rooms' => ['P101', 'P102', 'P202', 'P301'],

    // Thời hạn sử dụng gói trị liệu sau khi mua (ngày)
    'therapy_validity_days' => (int) env('GYM_THERAPY_VALIDITY_DAYS', 90),
];
