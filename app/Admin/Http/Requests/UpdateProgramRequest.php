<?php

namespace App\Admin\Http\Requests;

use App\Share\Enums\FileType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramRequest extends FormRequest
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
        $coverPrefix = (string) config('app_file.'.FileType::ProgramCover.'.prefix_path');

        $rules = [
            'rating' => ['nullable', 'numeric', 'between:0,5'],
        ];

        foreach ($locales as $locale) {
            $isRequiredLocale = $locale === $requiredLocale;

            // name: required ở locale mặc định, optional ở locale khác
            $rules["translations.{$locale}.name"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'string',
                'max:255',
            ];

            $rules["translations.{$locale}.description"] = ['nullable', 'string'];

            $rules["translations.{$locale}.sort"] = ['nullable', 'integer', 'min:0'];

            // cover: required ở locale mặc định
            $rules["translations.{$locale}.cover"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'array',
            ];
            $rules["translations.{$locale}.cover.path"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'string',
                'starts_with:'.$coverPrefix.'/',
            ];
            $rules["translations.{$locale}.cover.name"] = ['nullable', 'string'];
            $rules["translations.{$locale}.cover.extension"] = ['nullable', 'string'];
            $rules["translations.{$locale}.cover.size"] = ['nullable', 'integer'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['rating' => 'điểm đánh giá'];

        foreach ((array) config('translatable.locales') as $locale) {
            $attributes["translations.{$locale}.name"] = "tên ({$locale})";
            $attributes["translations.{$locale}.cover.path"] = "ảnh cover ({$locale})";
        }

        return $attributes;
    }
}
