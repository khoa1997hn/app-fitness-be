<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use App\Share\Models\Banner;
use App\Share\Models\Combo;
use App\Share\Models\ComboInfo;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use Illuminate\Database\Eloquent\Model;

trait MapsContent
{
    private function translations(Model $model, array $fields): array
    {
        $result = [];
        foreach ($model->translations as $translation) {
            $result[$translation->locale] = [];
            foreach ($fields as $field) {
                $result[$translation->locale][$field] = $translation->{$field};
            }
        }

        return $result;
    }

    private function mapProgram(Program $program, bool $withLessons = false): array
    {
        $data = [
            'id' => $program->id,
            'rating' => $program->rating,
            'translations' => $this->translations($program, ['name', 'description', 'cover', 'sort']),
            'favorites_count' => $program->favorites_count,
            'lessons_count' => $program->lessons_count,
            'duration_seconds' => $program->totalDurationSeconds(),
        ];
        if ($withLessons) {
            $data['lessons'] = $program->lessons->map(fn (Lesson $lesson) => $this->mapLesson($lesson))->all();
        }

        return $data;
    }

    private function mapLesson(Lesson $lesson): array
    {
        $video = $lesson->videos->first();

        return [
            'id' => $lesson->id,
            'program_id' => $lesson->program_id,
            'type' => $lesson->type,
            'level' => $lesson->level,
            'day' => $lesson->day,
            'favorites_count' => $lesson->favorites_count ?? 0,
            'translations' => $this->translations($lesson, ['name', 'description', 'teacher_name', 'thumbnail']),
            'video' => $video ? ['id' => $video->id, 'file' => $video->file, 'duration_seconds' => $video->duration_seconds] : null,
        ];
    }

    private function mapBanner(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'description' => $banner->description,
            'translations' => $this->translations($banner, ['image', 'link_url', 'order', 'is_active']),
        ];
    }

    private function mapCombo(Combo $combo): array
    {
        return [
            'id' => $combo->id,
            'translations' => $this->translations($combo, ['name', 'cover']),
            'program_ids' => $combo->programs->pluck('id')->all(),
            'infos' => $combo->infos->map(fn (ComboInfo $info) => [
                'id' => $info->id,
                'icon' => $info->icon,
                'translations' => $this->translations($info, ['text']),
            ])->all(),
        ];
    }

    private function page($paginator, callable $map): array
    {
        return [
            'items' => $paginator->getCollection()->map($map)->all(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
        ];
    }
}
