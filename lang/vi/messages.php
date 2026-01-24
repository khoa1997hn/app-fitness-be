<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines contain messages used throughout the
    | application. These messages are used for various purposes such as
    | success messages, error messages, and general information.
    |
    */

    'registration_success' => 'Đăng ký thành công',

    // Auth messages
    'login_success' => 'Đăng nhập thành công',
    'logout_success' => 'Đăng xuất thành công',
    'refresh_success' => 'Làm mới token thành công',
    'login_failed' => 'Email hoặc mật khẩu không đúng.',

    // Error messages
    'validation_error' => 'Dữ liệu không hợp lệ.',
    'authentication_error' => 'Bạn chưa đăng nhập hoặc phiên đăng nhập đã hết hạn.',
    'authorization_error' => 'Bạn không có quyền thực hiện hành động này.',
    'not_found_error' => 'Không tìm thấy tài nguyên được yêu cầu.',
    'server_error' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.',

    // Subscription messages
    'receipt_verified_successfully' => 'Xác thực receipt thành công.',
    'purchase_verified_successfully' => 'Xác thực purchase thành công.',
    'invalid_receipt' => 'Receipt không hợp lệ (:detail).',

];
