<x-mail::message>
@include('mail.partials.heading', ['headingVi' => 'Xác thực email của bạn', 'headingEn' => 'Verify your email address', 'recipientName' => $firstName])

Bạn vừa đăng ký tài khoản KORE. Vui lòng xác thực email để đăng nhập.

You have registered for a KORE account. Please verify your email address to sign in.

<x-mail::panel>
Link xác thực có hiệu lực trong **{{ $hours }} giờ**.<br>
This verification link is valid for **{{ $hours }} hours**.
</x-mail::panel>

Nếu bạn không đăng ký tài khoản, hãy bỏ qua email này.

If you did not create an account, you can ignore this email.

@include('mail.partials.action', ['actionUrl' => $url, 'actionVi' => 'Xác thực email', 'actionEn' => 'Verify email'])
</x-mail::message>
