<?php

namespace App\Admin\Http\Requests;

use App\Share\Enums\FileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComboRequest extends FormRequest
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
        $coverPrefix = (string) config('app_file.'.FileType::ComboCover.'.prefix_path');
        $iconPrefix = (string) config('app_file.'.FileType::ComboInfoIcon.'.prefix_path');

        $rules = [
            'program_ids' => ['required', 'array', 'min:2', 'max:7'],
            'program_ids.*' => ['integer', 'distinct', Rule::exists('programs', 'id')],
            'infos' => ['nullable', 'array', 'max:3'],
        ];

        foreach ($locales as $locale) {
            $isRequiredLocale = $locale === $requiredLocale;

            $rules["translations.{$locale}.name"] = [
                $isRequiredLocale ? 'required' : 'nullable',
                'string',
                'max:255',
            ];

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

        $infoCount = count((array) $this->input('infos', []));

        for ($index = 0; $index < $infoCount; $index++) {
            $rules["infos.{$index}.icon"] = ['required', 'array'];
            $rules["infos.{$index}.icon.path"] = ['required', 'string', 'starts_with:'.$iconPrefix.'/'];
            $rules["infos.{$index}.icon.name"] = ['nullable', 'string'];
            $rules["infos.{$index}.icon.extension"] = ['nullable', 'string'];
            $rules["infos.{$index}.icon.size"] = ['nullable', 'integer'];

            foreach ($locales as $locale) {
                $isRequiredLocale = $locale === $requiredLocale;

                $rules["infos.{$index}.translations.{$locale}.text"] = [
                    $isRequiredLocale ? 'required' : 'nullable',
                    'string',
                    'max:100',
                ];
            }
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'program_ids' => 'bộ môn',
            'program_ids.*' => 'bộ môn',
            'infos' => 'thông tin bổ sung',
        ];

        foreach ((array) config('translatable.locales') as $locale) {
            $attributes["translations.{$locale}.name"] = "tên combo ({$locale})";
            $attributes["translations.{$locale}.cover.path"] = "ảnh cover ({$locale})";
        }

        $infoCount = count((array) $this->input('infos', []));

        for ($index = 0; $index < $infoCount; $index++) {
            $attributes["infos.{$index}.icon.path"] = 'icon thông tin bổ sung #'.($index + 1);

            foreach ((array) config('translatable.locales') as $locale) {
                $attributes["infos.{$index}.translations.{$locale}.text"] = 'nội dung thông tin #'.($index + 1)." ({$locale})";
            }
        }

        return $attributes;
    }
}
