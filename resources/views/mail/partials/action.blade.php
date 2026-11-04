<x-mail::button :url="$actionUrl">
{{ $actionVi }} / {{ $actionEn }}
</x-mail::button>

<x-slot:subcopy>
Nếu nút phía trên không hoạt động, hãy mở đường dẫn bên dưới.<br>
If the button does not work, open the link below.<br>
<a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
</x-slot:subcopy>
