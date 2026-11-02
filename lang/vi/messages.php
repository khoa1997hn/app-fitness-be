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
    'password_reset_email_sent' => 'Nếu email đã đăng ký, mật khẩu mới đã được gửi tới hộp thư của bạn.',

    // Hệ thống phòng tập
    'gym_package_unavailable' => 'Gói tập không còn mở bán.',
    'gym_package_has_orders' => 'Gói đã có đơn hàng nên không thể xóa, hãy dùng Ẩn.',
    'gym_member_required' => 'Chỉ tạo được đơn hàng cho tài khoản hội viên.',
    'gym_terms_already_accepted' => 'Đơn hàng đã được xác nhận điều khoản.',
    'gym_terms_required' => 'Vui lòng xác nhận điều khoản và ký trước khi thanh toán.',
    'gym_payment_unavailable' => 'Thanh toán hiện chưa khả dụng.',
    'gym_extra_people_order_name' => 'Suất người tham gia thêm × :quantity',
    'gym_request_not_private' => 'Gói này không gửi được yêu cầu Private.',
    'gym_request_time_passed' => 'Vui lòng chọn thời gian sau hiện tại.',
    'gym_request_not_started' => 'Buổi tập chưa diễn ra.',
    'gym_request_people_limit' => 'Số người tham gia vượt giới hạn :max người của gói.',
    'gym_request_extra_not_allowed' => 'Gói này không được thêm người.',
    'gym_request_extra_session_limit' => 'Vượt quá giới hạn :max người thêm trong một buổi.',
    'gym_request_extra_insufficient' => 'Số dư suất người tham gia thêm không đủ.',
    'gym_request_invalid_status' => 'Yêu cầu không ở trạng thái phù hợp.',
    'gym_request_cannot_cancel' => 'Không thể hủy trong vòng :hours tiếng trước giờ tập.',
    'gym_booking_private_package' => 'Gói Private đặt lịch bằng form yêu cầu.',
    'gym_booking_schedule_unavailable' => 'Buổi học không còn mở đặt.',
    'gym_booking_package_expired' => 'Gói hết hạn trước ngày học.',
    'gym_booking_off_peak_only' => 'Gói off-peak chỉ đặt được lớp trong khung giờ off-peak.',
    'gym_booking_duplicate' => 'Bạn đã đặt buổi này.',
    'gym_booking_full' => 'Buổi học đã hết chỗ.',
    'gym_booking_no_credits' => 'Gói không còn buổi để đặt.',
    'gym_booking_already_cancelled' => 'Lịch này đã được hủy trước đó.',
    'gym_booking_cancel_locked' => 'Không thể hủy trong vòng :hours tiếng trước giờ tập.',
    'gym_booking_already_deducted' => 'Buổi này đã trừ, không thể hủy.',
    'gym_account_locked' => 'Tài khoản đã bị khóa. Vui lòng liên hệ phòng tập.',
    'gym_account_has_gym_data' => 'Tài khoản đã có gói hoặc đơn hàng phòng tập nên không thể xóa. Tài khoản chỉ có thể bị khóa, vui lòng liên hệ phòng tập.',
    'gym_account_has_schedules' => 'Tài khoản đã có lịch dạy nên không thể xóa, hãy khóa tài khoản.',
    'gym_user_self_action' => 'Không thể thực hiện thao tác này trên chính tài khoản của bạn.',
    'gym_password_incorrect' => 'Mật khẩu hiện tại không đúng.',
    'gym_extend_not_expired' => 'Gói chưa hết hạn, không cần gia hạn.',
    'gym_extend_no_credits' => 'Gói đã dùng hết buổi, không thể gia hạn.',
    'gym_notify_payment_success' => 'Thanh toán thành công: :name.',
    'gym_notify_booking_cancelled' => 'Lịch :name lúc :time đã bị phòng tập hủy. Bạn không bị trừ buổi.',
    'gym_notify_request_approved' => 'Yêu cầu :name lúc :time đã được duyệt.',
    'gym_notify_request_rejected' => 'Yêu cầu :name lúc :time đã bị từ chối.',
    'gym_notify_reminder' => 'Nhắc lịch: :name lúc :time.',
    'gym_notify_package_expiring' => 'Gói :name sẽ hết hạn vào :date.',
    'gym_notify_package_extended' => 'Gói :name đã được gia hạn đến :date.',
    'gym_event_not_on_sale' => 'Sự kiện không còn mở đăng ký.',
    'gym_event_sold_out' => 'Sự kiện không còn đủ vé.',
    'gym_event_already_registered' => 'Bạn đã đăng ký sự kiện này.',
    'gym_event_quantity_limit' => 'Mỗi lần đăng ký tối đa :max vé.',
    'gym_event_free_only' => 'Sự kiện này có phí, vui lòng tạo đơn hàng để mua vé.',
    'gym_event_paid_only' => 'Sự kiện này miễn phí, vui lòng đăng ký trực tiếp.',
    'gym_event_hold_expired' => 'Thời gian giữ vé đã hết, vui lòng đăng ký lại.',
    'gym_event_tickets_below_reserved' => 'Số vé không được nhỏ hơn số đã bán và đang giữ (:count).',
    'gym_event_date_future' => 'Ngày sự kiện phải sau thời điểm hiện tại.',
    'gym_event_status_invalid' => 'Không thể chuyển sự kiện sang trạng thái này.',
    'gym_event_cancelled_locked' => 'Sự kiện đã hủy, không thể sửa.',
    'gym_checkin_not_paid' => 'Chỉ check-in được vé đã thanh toán.',
    'gym_checkin_duplicate' => 'Vé này đã check-in.',
    'gym_order_cancelled' => 'Đơn hàng đã bị hủy.',
    'gym_event_order_name' => 'Vé :name × :quantity',
    'gym_notify_event_registered' => 'Đăng ký sự kiện :name thành công. Mã vé: :code.',
    'gym_notify_event_paid' => 'Thanh toán vé sự kiện :name thành công. Mã vé: :code.',
    'gym_notify_event_cancelled' => 'Sự kiện :name đã bị hủy.:reason Nếu đã thanh toán, phòng tập sẽ liên hệ hoàn tiền.',
    'gym_notify_event_cancelled_reason' => ' Lý do: :reason.',
    'gym_notify_event_hold_expired' => 'Đã hết thời gian giữ vé sự kiện :name, vé đã được nhả.',
    'gym_redlight_unavailable' => 'Buổi này không dùng được Redlight, hoặc đã đặt thêm rồi.',
    'gym_redlight_order_name' => 'Redlight — :name lúc :time',
    'gym_attendance_today_only' => 'Chỉ được điểm danh trong ngày diễn ra buổi học.',
    'gym_attendance_invalid_booking' => 'Học viên không còn trong buổi học này.',
    'gym_teaching_not_cancellable' => 'Lịch dạy này không thể hủy.',
    'gym_cancel_request_started' => 'Buổi dạy đã bắt đầu, không thể gửi yêu cầu hủy.',
    'gym_cancel_request_duplicate' => 'Lịch này đã có yêu cầu hủy đang chờ duyệt.',
    'gym_cancel_request_reviewed' => 'Yêu cầu hủy này đã được xử lý.',
    'gym_cancel_request_trainer_changed' => 'Lớp đã đổi HLV, yêu cầu hủy này không còn áp dụng.',

    // Error messages
    'validation_error' => 'Dữ liệu không hợp lệ.',
    'authentication_error' => 'Bạn chưa đăng nhập hoặc phiên đăng nhập đã hết hạn.',
    'authorization_error' => 'Bạn không có quyền thực hiện hành động này.',
    'not_found_error' => 'Không tìm thấy tài nguyên được yêu cầu.',
    'server_error' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.',

    // Subscription messages
    'receipt_verified_successfully' => 'Xác thực giao dịch thành công.',
    'purchase_verified_successfully' => 'Xác thực purchase thành công.',
    'invalid_receipt' => 'Giao dịch không hợp lệ (:detail).',

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
