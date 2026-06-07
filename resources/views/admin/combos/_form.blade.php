@php
    $locales = (array) config('translatable.locales');
    $requiredLocale = (string) config('translatable.fallback_locale');
    $localeLabel = fn (string $locale) => ucfirst(\Locale::getDisplayLanguage($locale, app()->getLocale()) ?: $locale);
    $infos = $infos ?? [];
@endphp

{{-- Tên combo --}}
<div class="mb-5">
    <label class="form-label">Tên combo</label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($locales as $locale)
            <div class="input-area">
                <span class="text-xs font-medium text-slate-500 mb-1 block">
                    {{ $localeLabel($locale) }}@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif
                </span>
                <input type="text" name="translations[{{ $locale }}][name]" class="form-control" maxlength="255"
                    value="{{ old('translations.'.$locale.'.name', $combo?->translate($locale)?->name) }}">
                @error('translations.'.$locale.'.name')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
            </div>
        @endforeach
    </div>
</div>

{{-- Cover --}}
<div class="mb-5">
    <label class="form-label">Ảnh cover</label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($locales as $locale)
            <div class="input-area">
                <span class="text-xs font-medium text-slate-500 mb-1 block">
                    {{ $localeLabel($locale) }}@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif
                </span>
                <input type="file" accept="image/*" class="form-control combo-cover-input" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][cover][path]" value="{{ old('translations.'.$locale.'.cover.path', $combo?->translate($locale)?->cover?->path) }}" class="combo-cover-path" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][cover][name]" value="{{ old('translations.'.$locale.'.cover.name', $combo?->translate($locale)?->cover?->name) }}" class="combo-cover-name" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][cover][extension]" value="{{ old('translations.'.$locale.'.cover.extension', $combo?->translate($locale)?->cover?->extension) }}" class="combo-cover-extension" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][cover][size]" value="{{ old('translations.'.$locale.'.cover.size', $combo?->translate($locale)?->cover?->size) }}" class="combo-cover-size" data-locale="{{ $locale }}">
                <span class="combo-cover-status text-sm text-slate-500" data-locale="{{ $locale }}"></span>
                @error('translations.'.$locale.'.cover.path')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                @php
                    $oldPath = old('translations.'.$locale.'.cover.path', $combo?->translate($locale)?->cover?->path);
                    $previewUrl = $oldPath
                        ? \App\Share\Attributes\File::fromArray([
                            'path' => $oldPath,
                            'name' => old('translations.'.$locale.'.cover.name', $combo?->translate($locale)?->cover?->name ?? ''),
                            'extension' => old('translations.'.$locale.'.cover.extension', $combo?->translate($locale)?->cover?->extension),
                            'size' => old('translations.'.$locale.'.cover.size', $combo?->translate($locale)?->cover?->size),
                        ])->url()
                        : null;
                @endphp
                <img src="{{ $previewUrl }}" alt="cover" data-locale="{{ $locale }}"
                    class="combo-cover-preview w-32 h-20 object-cover rounded mt-2 {{ $previewUrl ? '' : 'hidden' }}">
            </div>
        @endforeach
    </div>
</div>

{{-- Bộ môn --}}
<div class="mb-5">
    <label class="form-label">Bộ môn trong combo <span class="text-red-500">*</span> <span class="text-xs text-slate-500">(chọn từ 2 đến 7)</span></label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        @foreach ($programs as $program)
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="program_ids[]" value="{{ $program->id }}" class="form-checkbox"
                    {{ in_array($program->id, (array) $selectedProgramIds, true) ? 'checked' : '' }}>
                <span>{{ $program->name }}</span>
            </label>
        @endforeach
    </div>
    @error('program_ids')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
    @error('program_ids.*')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
</div>

{{-- Thông tin bổ sung --}}
<div class="mb-5">
    <div class="flex items-center justify-between mb-3">
        <label class="form-label mb-0">Thông tin bổ sung <span class="text-xs text-slate-500">(tối đa 3, icon PNG)</span></label>
        <button type="button" id="add-info-btn" class="btn btn-sm btn-outline-primary" @if(count($infos) >= 3) disabled @endif>
            <iconify-icon icon="heroicons-outline:plus" class="mr-1"></iconify-icon> Thêm
        </button>
    </div>
    <div id="infos-container" class="space-y-4">
        @foreach ($infos as $index => $info)
            @include('admin.combos._info_row', ['index' => $index, 'info' => $info])
        @endforeach
    </div>
    @error('infos')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
</div>

<template id="info-row-template">
    @include('admin.combos._info_row', ['index' => '__INDEX__', 'info' => []])
</template>
