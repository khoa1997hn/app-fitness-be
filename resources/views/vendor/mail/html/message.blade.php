<x-mail::layout>
<x-slot:head>
<style>
@media only screen and (max-width: 600px) {
.header { padding: 24px 16px 20px !important; }
.brand { width: 100% !important; }
.body { padding: 0 16px !important; }
.content-cell { padding: 28px 24px !important; }
h1 { font-size: 24px !important; }
}
</style>
</x-slot:head>
<x-slot:header>
<tr>
<td class="header" align="center">
<table class="brand" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#4a1c21">
<tr>
<td class="brand-cell">
<a href="{{ rtrim(config('app.frontend_url'), '/') }}" aria-label="KORE Online Studio">
<img src="https://kore.doratekjsc.com/assets/Kore%20Logo-06.png" class="logo" width="150" alt="KORE" />
</a>
<p class="brand-tagline">MOVE WELL · LIVE BETTER</p>
</td>
</tr>
</table>
</td>
</tr>
</x-slot:header>

{!! $slot !!}

Trân trọng / Kind regards,<br>
**Đội ngũ KORE / The KORE team**

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
[**KORE Online Studio**]({{ rtrim(config('app.frontend_url'), '/') }})<br>
MOVE WELL · LIVE BETTER<br>
[Đăng nhập / Sign in]({{ rtrim(config('app.frontend_url'), '/') }}/login)<br>
© {{ date('Y') }} KORE. Bảo lưu mọi quyền. / All rights reserved.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
