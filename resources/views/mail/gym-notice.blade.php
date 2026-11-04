<x-mail::message>
@include('mail.partials.heading', ['headingVi' => $title, 'headingEn' => $titleEn, 'recipientName' => $firstName])

<x-mail::panel>
{{ $notice }}

{{ $noticeEn }}
</x-mail::panel>

Bạn có thể truy cập thông tin liên quan bằng nút bên dưới. Vui lòng đăng nhập nếu được yêu cầu.

Use the button below to view the relevant information. Please sign in if prompted.

@include('mail.partials.action', ['actionUrl' => rtrim(config('app.frontend_url'), '/').$actionPath, 'actionVi' => 'Xem chi tiết', 'actionEn' => 'View details'])
</x-mail::message>
