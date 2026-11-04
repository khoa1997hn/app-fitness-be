<?php

use App\Share\Enums\FitnessClassCategory;

return [
    /*
    | Thanh toán mock: xác nhận thành công ngay, không qua cổng thật.
    | Mặc định tắt trên production vì mock cấp gói miễn phí.
    */
    'payment_mock_enabled' => (bool) env('GYM_PAYMENT_MOCK_ENABLED', env('APP_ENV', 'production') !== 'production'),

    /*
    | File điều khoản học viên ký khi mua hàng, chữ ký được chèn vào lúc gửi bản sao.
    | Ô chữ ký tính theo tỉ lệ trang (0 đến 1), gốc ở góc trên bên trái. Thay file thì chỉnh lại các giá trị này và vị trí họ tên, ngày ký bên dưới.
    | File phải là PDF 1.4, thư viện chèn chữ ký không đọc được PDF nén kiểu mới.
    */
    'terms_document' => [
        'path' => env('GYM_TERMS_PDF_PATH', resource_path('terms/dieu-khoan-dich-vu.pdf')),
        'signature' => [
            'page' => (int) env('GYM_TERMS_SIGNATURE_PAGE', 1),
            'x' => (float) env('GYM_TERMS_SIGNATURE_X', 0.55),
            'y' => (float) env('GYM_TERMS_SIGNATURE_Y', 0.72),
            'width' => (float) env('GYM_TERMS_SIGNATURE_WIDTH', 0.35),
            'height' => (float) env('GYM_TERMS_SIGNATURE_HEIGHT', 0.13),
        ],
        // Họ tên và ngày ký, in trên cùng trang với chữ ký
        'signer' => [
            'x' => (float) env('GYM_TERMS_SIGNER_X', 0.55),
            'y' => (float) env('GYM_TERMS_SIGNER_Y', 0.855),
            'width' => (float) env('GYM_TERMS_SIGNER_WIDTH', 0.35),
        ],
    ],

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

    // Giữ vé sự kiện từng này phút chờ thanh toán, quá hạn tự nhả vé
    'event_hold_minutes' => (int) env('GYM_EVENT_HOLD_MINUTES', 30),

    // Giá dùng thảm Redlight cho một buổi (VNĐ)
    'redlight_price' => (int) env('GYM_REDLIGHT_PRICE', 30000),

    // Nhóm lớp được dùng Redlight: Barre, Pilates
    'redlight_categories' => [FitnessClassCategory::Barre, FitnessClassCategory::CorePilates],

    'rooms' => ['P101', 'P102', 'P202', 'P301'],

    // Thời hạn sử dụng gói trị liệu sau khi mua (ngày)
    'therapy_validity_days' => (int) env('GYM_THERAPY_VALIDITY_DAYS', 90),
];
