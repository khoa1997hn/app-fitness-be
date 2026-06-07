<?php

namespace App\Web\Http\Controllers\API\V1\Concerns;

use App\Share\Models\Lesson;

trait MapsLessonForApi
{
    /**
     * @param  array<int, array{watched_seconds: int, completed_percent: int}>  $lessonProgressMap
     * @return array<string, mixed>
     */
    private function mapLessonForApi(Lesson $lesson, bool $isFavorited, array $lessonProgressMap): array
    {
        return [
            'id' => $lesson->id,
            'video_id' => $lesson->videos->sortBy('id')->first()?->id,
            'day' => $lesson->day,
            'name' => $lesson->name,
            'description' => $lesson->description,
            'teacher_name' => $lesson->teacher_name,
            'thumbnail' => $lesson->thumbnail,
            'duration_seconds' => (int) $lesson->videos->sum('duration_seconds'),
            'is_favorited' => $isFavorited,
            'progress' => $lessonProgressMap[$lesson->id] ?? ['watched_seconds' => 0, 'completed_percent' => 0],
        ];
    }
}
