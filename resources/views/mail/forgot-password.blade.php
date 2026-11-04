<x-mail::message>
@include('mail.partials.heading', ['headingVi' => 'Mật khẩu mới của bạn', 'headingEn' => 'Your new password', 'recipientName' => $user->first_name])

Bạn đã yêu cầu đặt lại mật khẩu. Mật khẩu mới của bạn là:

You requested a password reset. Your new password is:

<x-mail::panel>
<span class="password">{{ $password }}</span>
</x-mail::panel>

Vui lòng đăng nhập bằng mật khẩu mới. Bạn có thể đổi mật khẩu sau khi đăng nhập.

Please sign in with your new password. You can change it after signing in.

Nếu bạn không yêu cầu thay đổi này, vui lòng liên hệ đội ngũ hỗ trợ.

If you did not request this change, please contact support.

@include('mail.partials.action', ['actionUrl' => rtrim(config('app.frontend_url'), '/').'/login', 'actionVi' => 'Đăng nhập', 'actionEn' => 'Sign in'])
</x-mail::message>
