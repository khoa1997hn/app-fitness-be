@extends('admin.layouts.app')

@section('title', 'Sửa bộ môn')

@php
    $locales = (array) config('translatable.locales');
    $requiredLocale = (string) config('translatable.fallback_locale');
    // Tên hiển thị locale lấy động (ext intl) — không hardcode "Tiếng Việt"/"Tiếng Anh".
    $localeLabel = fn (string $locale) => ucfirst(\Locale::getDisplayLanguage($locale, app()->getLocale()) ?: $locale);
    // Preview media: ưu tiên file vừa upload (old) để giữ qua validate fail, fallback giá trị server.
    $resolveMedia = fn (string $prefix, $serverFile) => old($prefix.'.path')
        ? \App\Share\Attributes\File::fromArray([
            'path' => old($prefix.'.path'),
            'name' => old($prefix.'.name'),
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
            <a href="{{ route('admin.programs.index') }}">Bộ môn</a>
            <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Sửa bộ môn #{{ $program->id }}
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="space-y-5">
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Thông tin bộ môn</h4>
        </header>
        <div class="card-body px-6 pb-6">
            <form action="{{ route('admin.programs.update', $program) }}" method="POST" id="program-form">
                @csrf
                @method('PUT')

                {{-- Tên --}}
                <div class="mb-5">
                    <label class="form-label">Tên <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $translation = $program->translate($locale); @endphp
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif</span>
                                <input type="text" name="translations[{{ $locale }}][name]" class="form-control"
                                    value="{{ old('translations.'.$locale.'.name', optional($translation)->name) }}">
                                @error('translations.'.$locale.'.name')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Mô tả --}}
                <div class="mb-5">
                    <label class="form-label">Mô tả</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $translation = $program->translate($locale); @endphp
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}</span>
                                <textarea name="translations[{{ $locale }}][description]" class="form-control" rows="3">{{ old('translations.'.$locale.'.description', optional($translation)->description) }}</textarea>
                                @error('translations.'.$locale.'.description')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Thứ tự (sort) --}}
                <div class="mb-5">
                    <label class="form-label">Thứ tự (sort)</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $translation = $program->translate($locale); @endphp
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}</span>
                                <input type="number" name="translations[{{ $locale }}][sort]" class="form-control"
                                    value="{{ old('translations.'.$locale.'.sort', optional($translation)->sort ?? 0) }}">
                                @error('translations.'.$locale.'.sort')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Ảnh cover --}}
                <div class="mb-5">
                    <label class="form-label">Ảnh cover <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($locales as $locale)
                            @php $cover = $resolveMedia('translations.'.$locale.'.cover', optional($program->translate($locale))->cover); @endphp
                            <div class="input-area">
                                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif</span>
                                <input type="file" accept="image/*" class="form-control cover-file-input" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][cover][path]" value="{{ old('translations.'.$locale.'.cover.path', optional($cover)->path) }}" class="cover-path" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][cover][name]" value="{{ old('translations.'.$locale.'.cover.name', optional($cover)->name) }}" class="cover-name" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][cover][extension]" value="{{ old('translations.'.$locale.'.cover.extension', optional($cover)->extension) }}" class="cover-extension" data-locale="{{ $locale }}">
                                <input type="hidden" name="translations[{{ $locale }}][cover][size]" value="{{ old('translations.'.$locale.'.cover.size', optional($cover)->size) }}" class="cover-size" data-locale="{{ $locale }}">
                                <span class="cover-status text-sm text-slate-500" data-locale="{{ $locale }}"></span>
                                @error('translations.'.$locale.'.cover.path')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                <img src="{{ $cover?->url() }}" alt="cover" data-locale="{{ $locale }}"
                                    class="cover-preview w-32 h-32 object-cover rounded mt-2 {{ $cover ? '' : 'hidden' }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="input-area mb-5">
                    <label class="form-label">Đánh giá (0.0 - 5.0)</label>
                    <input type="number" step="0.1" min="0" max="5" name="rating" class="form-control max-w-[200px]"
                        value="{{ old('rating', $program->rating) }}">
                    @error('rating')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('admin.programs.index') }}" class="btn btn-light">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <header class="card-header noborder">
            <div class="flex items-center justify-between gap-4">
                <h4 class="card-title">Danh sách bài học</h4>
                <a href="{{ route('admin.programs.lessons.create', $program) }}" class="btn btn-primary px-6">
                    <iconify-icon icon="heroicons-outline:plus" class="mr-2"></iconify-icon>
                    Thêm bài học
                </a>
            </div>
        </header>
        <div class="card-body px-6 pb-6">
            <div class="overflow-x-auto -mx-6">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                            <thead class="border-t border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="table-th">ID</th>
                                    <th scope="col" class="table-th">Tên</th>
                                    <th scope="col" class="table-th">Loại</th>
                                    <th scope="col" class="table-th">Cấp độ</th>
                                    <th scope="col" class="table-th">Ngày</th>
                                    <th scope="col" class="table-th">Số yêu thích</th>
                                    <th scope="col" class="table-th">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                @forelse ($lessons as $lesson)
                                    <tr>
                                        <td class="table-td">{{ $lesson->id }}</td>
                                        <td class="table-td">{{ $lesson->name }}</td>
                                        <td class="table-td">@include('admin.components.enum-badge', ['enum' => $lesson->type])</td>
                                        <td class="table-td">@include('admin.components.enum-badge', ['enum' => $lesson->level])</td>
                                        <td class="table-td">
                                            <span class="badge bg-info-500 text-info-500 bg-opacity-30 rounded-3xl">{{ $lesson->day }}</span>
                                        </td>
                                        <td class="table-td">
                                            <span class="inline-flex items-center gap-1">
                                                <iconify-icon icon="heroicons-solid:heart" class="text-danger-500"></iconify-icon>
                                                {{ $lesson->favorites_count }}
                                            </span>
                                        </td>
                                        <td class="table-td">
                                            <div class="relative">
                                                <div class="dropdown relative">
                                                    <button class="text-xl text-center block w-full" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <iconify-icon icon="heroicons-outline:dots-vertical"></iconify-icon>
                                                    </button>
                                                    <ul class="dropdown-menu min-w-[120px] absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700 shadow z-[2] float-left overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                                        <li>
                                                            <a href="{{ route('admin.programs.lessons.edit', [$program, $lesson]) }}" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white w-full text-left">
                                                                Sửa
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('admin.programs.lessons.destroy', [$program, $lesson]) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white w-full text-left" onclick="return confirm('Bạn có chắc chắn muốn xóa bài học này?')">
                                                                    Xóa
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="table-td text-center">Chưa có bài học</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($lessons->hasPages())
                <div class="mt-6 flex justify-end">
                    {{ $lessons->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cover-file-input').forEach(function (input) {
        input.addEventListener('change', async function () {
            if (!this.files.length) {
                return;
            }

            const locale = this.dataset.locale;
            const status = document.querySelector('.cover-status[data-locale="' + locale + '"]');
            status.textContent = 'Đang tải lên...';

            try {
                const meta = await window.AdminS3Upload.upload(this.files[0], 'program_cover');
                document.querySelector('.cover-path[data-locale="' + locale + '"]').value = meta.path;
                document.querySelector('.cover-name[data-locale="' + locale + '"]').value = meta.name;
                document.querySelector('.cover-extension[data-locale="' + locale + '"]').value = meta.extension || '';
                document.querySelector('.cover-size[data-locale="' + locale + '"]').value = meta.size || '';
                const preview = document.querySelector('.cover-preview[data-locale="' + locale + '"]');
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
