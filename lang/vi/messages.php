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

    // Program selection
    'no_active_subscription' => 'Bạn chưa có gói đăng ký đang hoạt động.',
    'program_selection_not_required' => 'Gói All Access không cần chọn program.',
    'program_selection_invalid_count' => 'Số program chọn phải từ 1 đến :max.',
    'program_not_found' => 'Một hoặc nhiều program không tồn tại.',

    // Subscription cancel renewal
    'subscription_cancel_renewal_success' => 'Đã hủy gia hạn tự động. Bạn vẫn dùng được đến hết kỳ hiện tại.',
    'subscription_cancel_apple_manual' => 'Vui lòng hủy gia hạn trong Cài đặt → Apple ID → Đăng ký trên thiết bị của bạn.',
    'subscription_cannot_cancel_renewal' => 'Gói đăng ký không thể hủy gia hạn ở trạng thái hiện tại.',

    // File upload
    'file_type_not_supported' => 'Loại file không được hỗ trợ cho presigned upload.',
    'file_type_not_configured' => 'Loại file chưa được cấu hình.',
    'file_mimetype_not_allowed' => 'Định dạng file không được phép.',
    'file_size_exceeded' => 'Dung lượng file vượt quá :max_kb KB.',
    'file_extension_required' => 'Tên file phải có phần mở rộng.',

    // Video play
    'video_access_denied' => 'Bạn không có quyền xem video này.',
    'video_program_not_selected' => 'Vui lòng chọn program trước khi xem bài học.',
    'video_lesson_type_not_allowed' => 'Loại bài học này không thuộc gói đăng ký của bạn.',
    'video_file_not_available' => 'Video chưa sẵn sàng cho ngôn ngữ này.',

];
