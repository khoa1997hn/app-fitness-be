<x-mail::message>
<p class="eyebrow">TÀI KHOẢN KORE</p>

# Xác thực email của bạn

<p class="greeting">Chào {{ $firstName }},</p>

Bạn vừa đăng ký tài khoản. Vui lòng xác thực email để đăng nhập.

<x-mail::button :url="$url">
Xác thực email
</x-mail::button>

<x-mail::panel>
Link xác thực có hiệu lực trong **{{ $hours }} giờ**.
</x-mail::panel>

Nếu bạn không đăng ký tài khoản, hãy bỏ qua email này.

Trân trọng,<br>
Đội ngũ KORE

<x-slot:subcopy>
Nếu nút phía trên không hoạt động, hãy sao chép và mở đường dẫn sau trong trình duyệt:<br>
<a href="{{ $url }}">{{ $url }}</a>
</x-slot:subcopy>
</x-mail::message>
