<x-mail::message>
# Chào {{ $firstName }},

Bạn vừa đăng ký tài khoản. Vui lòng xác thực email để đăng nhập.

<x-mail::button :url="$url">
Xác thực email
</x-mail::button>

Link có hiệu lực trong {{ $hours }} giờ. Nếu bạn không đăng ký tài khoản, hãy bỏ qua email này.

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>
