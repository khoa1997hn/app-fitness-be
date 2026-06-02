<?php

namespace App\Admin\Http\Requests;

use App\Share\Enums\FileType;
use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
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
        $imagePrefix = (string) config('app_file.'.FileType::BannerImage.'.prefix_path');

        $rules = [
            'description' => ['nullable', 'string', 'max:500'],
        ];

        foreach ($locales as $locale) {
            $isRequiredLocale = $locale === $requiredLocale;

            $rules["translations.{$locale}.image"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'array',
            ];
            $rules["translations.{$locale}.image.path"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'string',
                'starts_with:'.$imagePrefix.'/',
            ];
            $rules["translations.{$locale}.image.name"] = ['nullable', 'string'];
            $rules["translations.{$locale}.image.extension"] = ['nullable', 'string'];
            $rules["translations.{$locale}.image.size"] = ['nullable', 'integer'];

            $rules["translations.{$locale}.link_url"] = ['nullable', 'string', 'max:2048'];
            $rules["translations.{$locale}.order"] = ['nullable', 'integer', 'min:0'];
            $rules["translations.{$locale}.is_active"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['description' => 'mô tả'];

        foreach ((array) config('translatable.locales') as $locale) {
            $attributes["translations.{$locale}.image.path"] = "ảnh banner ({$locale})";
            $attributes["translations.{$locale}.link_url"] = "link URL ({$locale})";
        }

        return $attributes;
    }
}
