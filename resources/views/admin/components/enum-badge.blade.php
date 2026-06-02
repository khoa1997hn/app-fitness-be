{{--
    Badge màu cho label enum/status trong danh sách admin.
    Dùng: @include('admin.components.enum-badge', ['enum' => $lesson->type])
    - $enum: instance enum (BenSampo) hoặc null.
    - Màu lấy từ palette Dashcode (class có sẵn trong app.css), gán ổn định theo value
      (cùng value luôn cùng màu). null → text trung tính.
--}}
@php
    // Chỉ dùng màu Dashcode có sẵn trong app.css (CSS static, không JIT).
    $palette = ['primary', 'success', 'warning', 'info', 'danger'];
    $value = $enum?->value;
    $color = $value === null ? null : $palette[abs(crc32((string) $value)) % count($palette)];
    $label = $enum?->description ?? '—';
@endphp
@if ($color)
    <span class="badge bg-{{ $color }}-500 text-{{ $color }}-500 bg-opacity-30 rounded-3xl">{{ $label }}</span>
@else
    <span class="text-slate-400 dark:text-slate-500">{{ $label }}</span>
@endif
