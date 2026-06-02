<?php

namespace App\Admin\Http\Requests;

class UpdateLessonRequest extends StoreLessonRequest
{
    /**
     * Cập nhật ⇒ không upload video mới thì giữ key cũ, file optional.
     *
     * @return array<string, mixed>
     */
    protected function videoFileRules(string $videoPrefix): array
    {
        return [
            'video.file' => ['nullable', 'array'],
            'video.file.path' => ['nullable', 'string', 'starts_with:'.$videoPrefix.'/'],
        ];
    }
}
