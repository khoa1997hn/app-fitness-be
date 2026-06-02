@extends('admin.layouts.app')

@section('title', 'Thêm banner')

@php
    $locales = (array) config('translatable.locales');
    $requiredLocale = (string) config('translatable.fallback_locale');
    $localeLabel = fn (string $locale) => ucfirst(\Locale::getDisplayLanguage($locale, app()->getLocale()) ?: $locale);
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
            Thêm banner
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="space-y-5">
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Thêm banner mới</h4>
        </header>
        <div class="card-body px-6 pb-6">
            <form action="{{ route('admin.banners.store') }}" method="POST" id="banner-form">
                @csrf

                {{-- Mô tả --}}
                <div class="input-area mb-5">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="2" maxlength="500">{{ old('description') }}</textarea>
                    @error('description')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                {{-- Ảnh --}}
                <div class="mb-5">
                    <label class="form-label">Ảnh banner <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">
                                    {{ $localeLabel($locale) }}@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif
                                </span>
                                <input type="file" accept="image/*" class="form-control banner-image-input" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][path]" value="{{ old('translations.'.$locale.'.image.path') }}" class="banner-image-path" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][name]" value="{{ old('translations.'.$locale.'.image.name') }}" class="banner-image-name" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][extension]" value="{{ old('translations.'.$locale.'.image.extension') }}" class="banner-image-extension" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][image][size]" value="{{ old('translations.'.$locale.'.image.size') }}" class="banner-image-size" data-locale="{{ $locale }}">
                                <span class="banner-image-status text-sm text-slate-500" data-locale="{{ $locale }}"></span>
                                @error('translations.'.$locale.'.image.path')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                @php
                                    $oldPath = old('translations.'.$locale.'.image.path');
                                    $previewUrl = $oldPath
                                        ? \App\Share\Attributes\File::fromArray([
                                            'path' => $oldPath,
                                            'name' => old('translations.'.$locale.'.image.name', ''),
                                            'extension' => old('translations.'.$locale.'.image.extension'),
                                            'size' => old('translations.'.$locale.'.image.size'),
                                        ])->url()
                                        : null;
                                @endphp
                                <img src="{{ $previewUrl }}" alt="banner" data-locale="{{ $locale }}"
                                    class="banner-image-preview w-32 h-20 object-cover rounded mt-2 {{ $previewUrl ? '' : 'hidden' }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Link URL --}}
                <div class="mb-5">
                    <label class="form-label">Link URL</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}</span>
                                <input type="text" name="translations[{{ $locale }}][link_url]" class="form-control"
                                    value="{{ old('translations.'.$locale.'.link_url') }}" placeholder="https://...">
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
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}</span>
                                <input type="number" name="translations[{{ $locale }}][order]" class="form-control"
                                    value="{{ old('translations.'.$locale.'.order', 0) }}" min="0">
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
                            <div class="input-area flex items-center gap-3">
                                <span class="text-xs font-medium text-slate-500">{{ $localeLabel($locale) }}</span>
                                <input type="hidden" name="translations[{{ $locale }}][is_active]" value="0">
                                <input type="checkbox" name="translations[{{ $locale }}][is_active]" value="1"
                                    class="form-checkbox"
                                    {{ old('translations.'.$locale.'.is_active', '1') == '1' ? 'checked' : '' }}>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-light">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Tạo banner</button>
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
