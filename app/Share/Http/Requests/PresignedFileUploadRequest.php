<?php

namespace App\Share\Http\Requests;

use App\Share\Enums\FileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PresignedFileUploadRequest extends FormRequest
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
        return [
            'type' => ['required', 'string', Rule::in(FileType::getValues())],
            'filename' => ['required', 'string', 'max:255', 'regex:/\.[^.\/]+$/'],
            'mimetype' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $type = (string) $this->input('type');
            $config = config("app_file.{$type}");

            if (! is_array($config) || ! isset($config['allow_mimetypes'], $config['allow_max_size'])) {
                $validator->errors()->add('type', __('messages.file_type_not_configured'));

                return;
            }

            $mimetype = (string) $this->input('mimetype');

            if (! in_array($mimetype, $config['allow_mimetypes'], true)) {
                $validator->errors()->add('mimetype', __('messages.file_mimetype_not_allowed'));
            }

            $size = (int) $this->input('size');
            $maxBytes = (int) $config['allow_max_size'] * 1024;

            if ($size > $maxBytes) {
                $validator->errors()->add(
                    'size',
                    __('messages.file_size_exceeded', ['max_kb' => $config['allow_max_size']])
                );
            }
        });
    }
}
