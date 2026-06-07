@php
    $locales = (array) config('translatable.locales');
    $requiredLocale = (string) config('translatable.fallback_locale');
    $localeLabel = fn (string $locale) => ucfirst(\Locale::getDisplayLanguage($locale, app()->getLocale()) ?: $locale);
    $iconPath = $info['icon']['path'] ?? '';
    $iconPreviewUrl = $iconPath
        ? \App\Share\Attributes\File::fromArray([
            'path' => $iconPath,
            'name' => $info['icon']['name'] ?? '',
            'extension' => $info['icon']['extension'] ?? null,
            'size' => $info['icon']['size'] ?? null,
        ])->url()
        : null;
@endphp
<div class="info-row border border-slate-200 dark:border-slate-700 rounded p-4" data-index="{{ $index }}">
    <div class="flex items-center justify-between mb-3">
        <span class="font-medium text-sm">Thông tin #<span class="info-number">{{ is_numeric($index) ? $index + 1 : '' }}</span></span>
        <button type="button" class="btn btn-sm btn-outline-danger remove-info-btn">Xóa</button>
    </div>
    <div class="mb-3">
        <label class="form-label">Icon (PNG) <span class="text-red-500">*</span></label>
        <input type="file" accept="image/png" class="form-control combo-info-icon-input" data-index="{{ $index }}">
        <input type="hidden" name="infos[{{ $index }}][icon][path]" value="{{ $iconPath }}" class="combo-info-icon-path" data-index="{{ $index }}">
        <input type="hidden" name="infos[{{ $index }}][icon][name]" value="{{ $info['icon']['name'] ?? '' }}" class="combo-info-icon-name" data-index="{{ $index }}">
        <input type="hidden" name="infos[{{ $index }}][icon][extension]" value="{{ $info['icon']['extension'] ?? '' }}" class="combo-info-icon-extension" data-index="{{ $index }}">
        <input type="hidden" name="infos[{{ $index }}][icon][size]" value="{{ $info['icon']['size'] ?? '' }}" class="combo-info-icon-size" data-index="{{ $index }}">
        <span class="combo-info-icon-status text-sm text-slate-500" data-index="{{ $index }}"></span>
        @error('infos.'.$index.'.icon.path')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
        <img src="{{ $iconPreviewUrl }}" alt="icon" data-index="{{ $index }}"
            class="combo-info-icon-preview w-12 h-12 object-contain rounded mt-2 {{ $iconPreviewUrl ? '' : 'hidden' }}">
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($locales as $locale)
            <div class="input-area">
                <span class="text-xs font-medium text-slate-500 mb-1 block">
                    Nội dung ({{ $localeLabel($locale) }})@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif
                </span>
                <input type="text" name="infos[{{ $index }}][translations][{{ $locale }}][text]" class="form-control" maxlength="100"
                    value="{{ $info['translations'][$locale]['text'] ?? '' }}">
                @error('infos.'.$index.'.translations.'.$locale.'.text')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
            </div>
        @endforeach
    </div>
</div>
