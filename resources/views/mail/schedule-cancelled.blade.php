<x-mail::message>
@include('mail.partials.heading', ['headingVi' => 'Thông báo hủy lịch tập', 'headingEn' => 'Your session has been cancelled', 'recipientName' => $firstName])

<x-mail::panel>
{{ $notice }}

{{ $noticeEn }}
</x-mail::panel>

Bạn có thể xem lại lịch này trong mục **Lịch sử tập**, với trạng thái **Đã hủy**.

You can find this session in **Session history**, marked as **Cancelled**.

@include('mail.partials.action', ['actionUrl' => rtrim(config('app.frontend_url'), '/').'/hoc-vien/khoa-hoc-cua-toi?tab=history', 'actionVi' => 'Xem lịch sử tập', 'actionEn' => 'View session history'])
</x-mail::message>
