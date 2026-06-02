<?php

namespace App\Admin\Http\Controllers;

use App\Admin\Http\Requests\StoreLessonRequest;
use App\Admin\Http\Requests\UpdateLessonRequest;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\Video;

class LessonController extends BaseController
{
    public function create(Program $program)
    {
        return view('admin.lessons.create', compact('program'));
    }

    public function store(StoreLessonRequest $request, Program $program)
    {
        $validated = $request->validated();

        $lesson = new Lesson([
            'program_id' => $program->id,
            'type' => $validated['type'],
            'level' => $validated['level'] ?? null,
            'day' => $validated['day'],
        ]);
        $lesson->fill($this->translationPayload($validated));
        $lesson->save();

        $this->saveVideo($lesson, $validated, null);

        return redirect()
            ->route('admin.programs.edit', $program)
            ->with('success', 'Tạo bài học thành công.');
    }

    public function edit(Program $program, Lesson $lesson)
    {
        $lesson->load('translations', 'videos.translations')->loadCount('favorites');
        $video = $lesson->videos->first();

        return view('admin.lessons.edit', compact('program', 'lesson', 'video'));
    }

    public function update(UpdateLessonRequest $request, Program $program, Lesson $lesson)
    {
        $validated = $request->validated();

        $lesson->fill([
            'type' => $validated['type'],
            'level' => $validated['level'] ?? null,
            'day' => $validated['day'],
        ]);
        $lesson->fill($this->translationPayload($validated));
        $lesson->save();

        $this->saveVideo($lesson, $validated, $lesson->videos()->first());

        return redirect()
            ->route('admin.programs.edit', $program)
            ->with('success', 'Cập nhật bài học thành công.');
    }

    public function destroy(Program $program, Lesson $lesson)
    {
        $lesson->delete();

        return redirect()
            ->route('admin.programs.edit', $program)
            ->with('success', 'Xóa bài học thành công.');
    }

    /**
     * Gom payload các locale cho name/description/thumbnail của lesson.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function translationPayload(array $validated): array
    {
        $payload = [];

        foreach ((array) config('translatable.locales') as $locale) {
            $data = $validated['translations'][$locale] ?? null;

            if ($data === null || ($data['name'] ?? null) === null) {
                continue;
            }

            $payload[$locale] = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
            ];
        }

        return $payload;
    }

    /**
     * Lưu 1 video/lesson. Update đúng record cũ (tạo mới nếu chưa có).
     * Không upload video mới ⇒ giữ key cũ, chỉ cập nhật duration_seconds.
     *
     * @param  array<string, mixed>  $validated
     */
    private function saveVideo(Lesson $lesson, array $validated, ?Video $existing): void
    {
        $video = $existing ?? new Video(['lesson_id' => $lesson->id]);
        $newFile = $validated['video']['file'] ?? null;
        $duration = (int) $validated['video']['duration_seconds'];

        foreach ((array) config('translatable.locales') as $locale) {
            $payload = ['duration_seconds' => $duration];

            if ($newFile !== null) {
                $payload['file'] = $newFile;
            }

            $video->fill([$locale => $payload]);
        }

        $video->save();
    }
}
