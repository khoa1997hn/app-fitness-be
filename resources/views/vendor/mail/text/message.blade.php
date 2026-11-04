<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="config('app.frontend_url')">
            KORE Online Studio
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

Trân trọng / Kind regards,<br>
**Đội ngũ KORE / The KORE team**

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            [Đăng nhập / Sign in]({{ rtrim(config('app.frontend_url'), '/') }}/login)

            © {{ date('Y') }} KORE Online Studio. Bảo lưu mọi quyền. / All rights reserved.
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
