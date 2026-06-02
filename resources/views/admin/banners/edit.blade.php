@extends('admin.layouts.app')

@section('title', 'Sửa banner')

@php
    $locales = (array) config('translatable.locales');
    $requiredLocale = (string) config('translatable.fallback_locale');
    $localeLabel = fn (string $locale) => ucfirst(\Locale::getDisplayLanguage($locale, app()->getLocale()) ?: $locale);
    $resolveMedia = fn (string $prefix, $serverFile) => old($prefix.'.path')
        ? \App\Share\Attributes\File::fromArray([
            'path' => old($prefix.'.path'),
            'name' => old($prefix.'.name', ''),
            'extension' => old($prefix.'.extension'),
            'size' => old($prefix.'.size'),
        ])
        : $serverFile;
@endphp

@section('content')
<!-- BEGIN: Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none">
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.dashboard') }}">
                <iconify-icon icon="heroicons-outline:home"></iconify-icon>
                <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
            </a>
        </li>
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.banners.index') }}">Banner</a>
            <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Sửa banner #{{ $banner->id }}
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="space-y-5">
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Thông tin banner</h4>
        </header>
        <div class="card-body px-6 pb-6">
            <form action="{{ route('admin.banners.update', $banner) }}" method="POST" id="banner-form">
                @csrf
                @method('PUT')

                {{-- Mô tả --}}
                <div class="input-area mb-5">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="500">{{ old('description', $banner->description) }}</textarea>
                    @error('description')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                {{-- Ảnh --}}
                <div class="mb-5">
                    <label class="form-label">Ảnh banner <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $image = $resolveMedia('translations.'.$locale.'.image', optional($banner->translate($locale))->image); @endphp
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">
                                    {{ $localeLabel($locale) }}@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif
                                </span>
                                <input type="file" accept="image/*" class="form-control banner-image-input" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][path]" value="{{ old('translations.'.$locale.'.image.path', optional($image)->path) }}" class="banner-image-path" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][name]" value="{{ old('translations.'.$locale.'.image.name', optional($image)->name) }}" class="banner-image-name" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][extension]" value="{{ old('translations.'.$locale.'.image.extension', optional($image)->extension) }}" class="banner-image-extension" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][size]" value="{{ old('translations.'.$locale.'.image.size', optional($image)->size) }}" class="banner-image-size" data-locale="{{ $locale }}">
                                <span class="banner-image-status text-sm text-slate-500" data-locale="{{ $locale }}"></span>
                                @error('translations.'.$locale.'.image.path')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                <img src="{{ $image?->url() }}" alt="banner" data-locale="{{ $locale }}"
                                    class="banner-image-preview w-32 h-20 object-cover rounded mt-2 {{ $image ? '' : 'hidden' }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Link URL --}}
                <div class="mb-5">
                    <label class="form-label">Link URL</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $translation = $banner->translate($locale); @endphp
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}</span>
                                <input type="text" name="translations[{{ $locale }}][link_url]" class="form-control"
                                    value="{{ old('translations.'.$locale.'.link_url', optional($translation)->link_url) }}"
                                    placeholder="https://...">
                                @error('translations.'.$locale.'.link_url')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Thứ tự --}}
                <div class="mb-5">
                    <label class="form-label">Thứ tự hiển thị</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $translation = $banner->translate($locale); @endphp
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}</span>
                                <input type="number" name="translations[{{ $locale }}][order]" class="form-control"
                                    value="{{ old('translations.'.$locale.'.order', optional($translation)->order ?? 0) }}" min="0">
                                @error('translations.'.$locale.'.order')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Kích hoạt --}}
                <div class="mb-5">
                    <label class="form-label">Kích hoạt</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $translation = $banner->translate($locale); @endphp
                            <div class="input-area flex items-center gap-3">
                                <span class="text-xs font-medium text-slate-500">{{ $localeLabel($locale) }}</span>
                                <input type="hidden" name="translations[{{ $locale }}][is_active]" value="0">
                                <input type="checkbox" name="translations[{{ $locale }}][is_active]" value="1"
                                    class="form-checkbox"
                                    {{ old('translations.'.$locale.'.is_active', optional($translation)->is_active ?? true) ? 'checked' : '' }}>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-light">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.banner-image-input').forEach(function (input) {
        input.addEventListener('change', async function () {
            if (!this.files.length) return;

            const locale = this.dataset.locale;
            const status = document.querySelector('.banner-image-status[data-locale="' + locale + '"]');
            status.textContent = 'Đang tải lên...';

            try {
                const meta = await window.AdminS3Upload.upload(this.files[0], 'banner_image');
                document.querySelector('.banner-image-path[data-locale="' + locale + '"]').value = meta.path;
                document.querySelector('.banner-image-name[data-locale="' + locale + '"]').value = meta.name;
                document.querySelector('.banner-image-extension[data-locale="' + locale + '"]').value = meta.extension || '';
                document.querySelector('.banner-image-size[data-locale="' + locale + '"]').value = meta.size || '';
                const preview = document.querySelector('.banner-image-preview[data-locale="' + locale + '"]');
                if (preview) {
                    preview.src = URL.createObjectURL(this.files[0]);
                    preview.classList.remove('hidden');
                }
                status.textContent = 'Đã tải lên: ' + meta.name;
            } catch (error) {
                status.textContent = 'Lỗi: ' + error.message;
            }
        });
    });
</script>
@endpush
