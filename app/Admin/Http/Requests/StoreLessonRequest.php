<?php

namespace App\Admin\Http\Requests;

use App\Share\Enums\FileType;
use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request tạo bài học. UpdateLessonRequest kế thừa class này và chỉ override
 * videoFileRules() (tạo mới: video required; cập nhật: video optional) —
 * toàn bộ rule type/level/day + translations dùng chung ở đây.
 */
class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $requiredLocale = (string) config('translatable.fallback_locale');
        $locales = (array) config('translatable.locales');
        // Path do client gửi qua hidden input ⇒ ép phải nằm trong prefix S3 của đúng loại file
        // (chống gán key tùy ý trỏ tới object khác trong bucket → leak qua presigned GET).
        $thumbnailPrefix = (string) config('app_file.'.FileType::LessonThumbnail.'.prefix_path');
        $videoPrefix = (string) config('app_file.'.FileType::LessonVideo.'.prefix_path');

        $rules = [
            'type' => ['required', 'string', Rule::in(LessonType::getValues())],
            'level' => [
                'nullable',
                'string',
                Rule::in(Level::getValues()),
                Rule::requiredIf(fn () => $this->input('type') === LessonType::Level),
            ],
            'day' => ['required', 'integer', 'min:1'],
            'video.file.name' => ['nullable', 'string'],
            'video.file.extension' => ['nullable', 'string'],
            'video.file.size' => ['nullable', 'integer'],
            'video.duration_seconds' => ['required', 'integer', 'min:1'],
        ];

        $rules = array_merge($rules, $this->videoFileRules($videoPrefix));

        foreach ($locales as $locale) {
            $isRequiredLocale = $locale === $requiredLocale;

            $rules["translations.{$locale}.name"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'string',
                'max:255',
            ];
            $rules["translations.{$locale}.description"] = ['nullable', 'string'];
            $rules["translations.{$locale}.teacher_name"] = ['nullable', 'string', 'max:255'];

            $rules["translations.{$locale}.thumbnail"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'array',
            ];
            $rules["translations.{$locale}.thumbnail.path"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'string',
                'starts_with:'.$thumbnailPrefix.'/',
            ];
            $rules["translations.{$locale}.thumbnail.name"] = ['nullable', 'string'];
            $rules["translations.{$locale}.thumbnail.extension"] = ['nullable', 'string'];
            $rules["translations.{$locale}.thumbnail.size"] = ['nullable', 'integer'];
        }

        return $rules;
    }

    /**
     * Tạo mới ⇒ bắt buộc upload video. Update override để nới lỏng (giữ key cũ).
     *
     * @return array<string, mixed>
     */
    protected function videoFileRules(string $videoPrefix): array
    {
        return [
            'video.file' => ['required', 'array'],
            'video.file.path' => ['required', 'string', 'starts_with:'.$videoPrefix.'/'],
        ];
    }

    /**
     * type ≠ level ⇒ ép level = null (không tin client).
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== LessonType::Level) {
            $this->merge(['level' => null]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'type' => 'loại bài học',
            'level' => 'cấp độ',
            'day' => 'ngày',
            'video.file.path' => 'video',
            'video.duration_seconds' => 'thời lượng (giây)',
        ];

        foreach ((array) config('translatable.locales') as $locale) {
            $attributes["translations.{$locale}.name"] = "tên ({$locale})";
            $attributes["translations.{$locale}.teacher_name"] = "tên giáo viên ({$locale})";
            $attributes["translations.{$locale}.thumbnail.path"] = "ảnh thumbnail ({$locale})";
        }

        return $attributes;
    }
}
