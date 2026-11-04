<x-mail::message>
<p class="eyebrow">THÔNG BÁO TỪ KORE</p>

# {{ $title }}

<p class="greeting">Chào {{ $firstName }},</p>

<x-mail::panel>
{{ $notice }}
</x-mail::panel>

Trân trọng,<br>
Đội ngũ KORE
</x-mail::message>
