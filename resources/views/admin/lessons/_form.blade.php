@php
    $locales = (array) config('translatable.locales');
    $requiredLocale = (string) config('translatable.fallback_locale');
    $lesson = $lesson ?? null;
    $video = $video ?? null;
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

<div class="input-area mb-4 max-w-[300px]">
    <label class="form-label">Loại bài học <span class="text-red-500">*</span></label>
    <select name="type" id="lesson-type" class="form-control">
        @foreach (\App\Share\Enums\LessonType::asSelectArray() as $value => $label)
            <option value="{{ $value }}" @selected(old('type', optional($lesson)->type?->value) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('type')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
</div>

<div class="input-area mb-4 max-w-[300px]" id="lesson-level-wrapper">
    <label class="form-label">Cấp độ <span class="text-red-500">*</span></label>
    <select name="level" id="lesson-level" class="form-control">
        <option value="">-- Chọn cấp độ --</option>
        @foreach (\App\Share\Enums\Level::asSelectArray() as $value => $label)
            <option value="{{ $value }}" @selected(old('level', optional($lesson)->level?->value) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('level')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
</div>

<div class="input-area mb-4 max-w-[300px]">
    <label class="form-label">Ngày (thứ N trong lộ trình) <span class="text-red-500">*</span></label>
    <input type="number" min="1" name="day" class="form-control" value="{{ old('day', optional($lesson)->day) }}">
    @error('day')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
</div>

{{-- Tên --}}
<div class="mb-5">
    <label class="form-label">Tên <span class="text-red-500">*</span></label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($locales as $locale)
            @php $translation = $lesson ? $lesson->translate($locale) : null; @endphp
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
            @php $translation = $lesson ? $lesson->translate($locale) : null; @endphp
            <div class="input-area">
                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}</span>
                <textarea name="translations[{{ $locale }}][description]" class="form-control" rows="3">{{ old('translations.'.$locale.'.description', optional($translation)->description) }}</textarea>
                @error('translations.'.$locale.'.description')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
            </div>
        @endforeach
    </div>
</div>

{{-- Ảnh thumbnail --}}
<div class="mb-5">
    <label class="form-label">Ảnh thumbnail <span class="text-red-500">*</span></label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($locales as $locale)
            @php $thumbnail = $resolveMedia('translations.'.$locale.'.thumbnail', optional($lesson ? $lesson->translate($locale) : null)->thumbnail); @endphp
            <div class="input-area">
                <span class="text-xs font-medium text-slate-500 mb-1 block">{{ $localeLabel($locale) }}@if ($locale === $requiredLocale) <span class="text-red-500">*</span>@endif</span>
                <input type="file" accept="image/*" class="form-control thumbnail-file-input" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][thumbnail][path]" value="{{ old('translations.'.$locale.'.thumbnail.path', optional($thumbnail)->path) }}" class="thumbnail-path" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][thumbnail][name]" value="{{ old('translations.'.$locale.'.thumbnail.name', optional($thumbnail)->name) }}" class="thumbnail-name" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][thumbnail][extension]" value="{{ old('translations.'.$locale.'.thumbnail.extension', optional($thumbnail)->extension) }}" class="thumbnail-extension" data-locale="{{ $locale }}">
                <input type="hidden" name="translations[{{ $locale }}][thumbnail][size]" value="{{ old('translations.'.$locale.'.thumbnail.size', optional($thumbnail)->size) }}" class="thumbnail-size" data-locale="{{ $locale }}">
                <span class="thumbnail-status text-sm text-slate-500" data-locale="{{ $locale }}"></span>
                @error('translations.'.$locale.'.thumbnail.path')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                <img src="{{ $thumbnail?->url() }}" alt="thumbnail" data-locale="{{ $locale }}"
                    class="thumbnail-preview w-32 h-32 object-cover rounded mt-2 {{ $thumbnail ? '' : 'hidden' }}">
            </div>
        @endforeach
    </div>
</div>

<div class="border border-slate-200 dark:border-slate-700 rounded-md p-4 mb-5">
    <h5 class="text-base font-medium mb-4">Video</h5>

    @php
        $videoFile = $resolveMedia('video.file', $video?->file);
        $videoUrl = $videoFile?->url();
    @endphp
    <div class="mb-4 {{ $videoUrl ? '' : 'hidden' }}" id="video-preview-wrapper">
        <video controls class="max-w-full rounded" style="max-height: 360px;" id="video-preview">
            <source src="{{ $videoUrl }}" id="video-preview-source">
            Trình duyệt không hỗ trợ phát video.
        </video>
    </div>

    <div class="input-area mb-2">
        <label class="form-label">Tệp video @unless ($video)<span class="text-red-500">*</span>@endunless</label>
        <input type="file" accept="video/*" class="form-control" id="video-file-input">
        <input type="hidden" name="video[file][path]" value="{{ old('video.file.path', optional(optional($video)->file)->path) }}" id="video-path">
        <input type="hidden" name="video[file][name]" value="{{ old('video.file.name', optional(optional($video)->file)->name) }}" id="video-name">
        <input type="hidden" name="video[file][extension]" value="{{ old('video.file.extension', optional(optional($video)->file)->extension) }}" id="video-extension">
        <input type="hidden" name="video[file][size]" value="{{ old('video.file.size', optional(optional($video)->file)->size) }}" id="video-size">
        <span class="text-sm text-slate-500" id="video-status"></span>
        @error('video.file.path')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
    </div>

    <div class="input-area mt-4 max-w-[300px]">
        <label class="form-label">Thời lượng (giây) <span class="text-red-500">*</span></label>
        <input type="number" min="1" name="video[duration_seconds]" class="form-control"
            value="{{ old('video.duration_seconds', optional($video)->duration_seconds) }}">
        @error('video.duration_seconds')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const typeSelect = document.getElementById('lesson-type');
        const levelWrapper = document.getElementById('lesson-level-wrapper');
        const levelSelect = document.getElementById('lesson-level');
        const levelTypeValue = @json(\App\Share\Enums\LessonType::Level);

        function toggleLevel() {
            const isLevel = typeSelect.value === levelTypeValue;
            levelWrapper.style.display = isLevel ? '' : 'none';
            if (!isLevel) {
                levelSelect.value = '';
            }
        }

        typeSelect.addEventListener('change', toggleLevel);
        toggleLevel();

        // Thumbnail upload (presigned)
        document.querySelectorAll('.thumbnail-file-input').forEach(function (input) {
            input.addEventListener('change', async function () {
                if (!this.files.length) {
                    return;
                }
                const locale = this.dataset.locale;
                const status = document.querySelector('.thumbnail-status[data-locale="' + locale + '"]');
                status.textContent = 'Đang tải lên...';
                try {
                    const meta = await window.AdminS3Upload.upload(this.files[0], 'lesson_thumbnail');
                    document.querySelector('.thumbnail-path[data-locale="' + locale + '"]').value = meta.path;
                    document.querySelector('.thumbnail-name[data-locale="' + locale + '"]').value = meta.name;
                    document.querySelector('.thumbnail-extension[data-locale="' + locale + '"]').value = meta.extension || '';
                    document.querySelector('.thumbnail-size[data-locale="' + locale + '"]').value = meta.size || '';
                    const preview = document.querySelector('.thumbnail-preview[data-locale="' + locale + '"]');
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

        // Video upload (presigned)
        const videoInput = document.getElementById('video-file-input');
        videoInput.addEventListener('change', async function () {
            if (!this.files.length) {
                return;
            }
            const status = document.getElementById('video-status');
            status.textContent = 'Đang tải lên video...';
            try {
                const meta = await window.AdminS3Upload.upload(this.files[0], 'lesson_video');
                document.getElementById('video-path').value = meta.path;
                document.getElementById('video-name').value = meta.name;
                document.getElementById('video-extension').value = meta.extension || '';
                document.getElementById('video-size').value = meta.size || '';
                const wrapper = document.getElementById('video-preview-wrapper');
                const source = document.getElementById('video-preview-source');
                const player = document.getElementById('video-preview');
                source.src = URL.createObjectURL(this.files[0]);
                player.load();
                wrapper.classList.remove('hidden');
                status.textContent = 'Đã tải lên: ' + meta.name;
            } catch (error) {
                status.textContent = 'Lỗi: ' + error.message;
            }
        });
    })();
</script>
@endpush
