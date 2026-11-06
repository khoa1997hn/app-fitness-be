<x-mail::message>
@include('mail.partials.heading', ['headingVi' => 'Đặt lại mật khẩu', 'headingEn' => 'Reset your password', 'recipientName' => $user->first_name])

Bạn đã yêu cầu đặt lại mật khẩu. Nhấn nút bên dưới để xác nhận và nhập mật khẩu mới.

You requested a password reset. Click the button below to confirm and enter a new password.

Liên kết có hiệu lực trong {{ config('auth.passwords.users.expire') }} phút và chỉ sử dụng được một lần. Mật khẩu hiện tại vẫn giữ nguyên cho tới khi bạn hoàn tất.

This link expires in {{ config('auth.passwords.users.expire') }} minutes and can only be used once. Your current password remains unchanged until you complete the reset.

Nếu bạn không yêu cầu thay đổi này, hãy bỏ qua email này.

If you did not request this change, you can ignore this email.

@include('mail.partials.action', ['actionUrl' => $url, 'actionVi' => 'Đặt lại mật khẩu', 'actionEn' => 'Reset password'])
</x-mail::message>
