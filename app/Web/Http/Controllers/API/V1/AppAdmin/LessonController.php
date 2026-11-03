<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use App\Admin\Http\Requests\StoreLessonRequest;
use App\Admin\Http\Requests\UpdateLessonRequest;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\Video;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseController;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class LessonController extends BaseController
{
    use MapsContent;

    #[OA\Get(
        path: '/admin/app/programs/{program}/lessons/{lesson}',
        summary: 'Chi tiết Lesson',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'lesson', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminLesson')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function show(Program $program, Lesson $lesson)
    {
        $lesson->load(['translations', 'videos.translations'])->loadCount('favorites');

        return ResponseAPI::success($this->mapLesson($lesson));
    }

    #[OA\Post(
        path: '/admin/app/programs/{program}/lessons',
        summary: 'Tạo Lesson',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminLessonInput')),
        responses: [
            new OA\Response(response: 201, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminLesson')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(StoreLessonRequest $request, Program $program)
    {
        $validated = $request->validated();

        $lesson = DB::transaction(function () use ($validated, $program) {
            $lesson = new Lesson([
                'program_id' => $program->id,
                'type' => $validated['type'],
                'level' => $validated['level'] ?? null,
                'day' => $validated['day'],
            ]);
            $lesson->fill($this->translationPayload($validated));
            $lesson->save();

            $this->saveVideo($lesson, $validated, null);

            return $lesson;
        });

        $lesson->load(['translations', 'videos.translations']);

        return ResponseAPI::success($this->mapLesson($lesson), code: 201);
    }

    #[OA\Put(
        path: '/admin/app/programs/{program}/lessons/{lesson}',
        summary: 'Cập nhật Lesson',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'lesson', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminLessonInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    #[OA\Patch(
        path: '/admin/app/programs/{program}/lessons/{lesson}',
        summary: 'Cập nhật Lesson',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'lesson', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminLessonInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(UpdateLessonRequest $request, Program $program, Lesson $lesson)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $lesson) {
            $lesson->fill([
                'type' => $validated['type'],
                'level' => $validated['level'] ?? null,
                'day' => $validated['day'],
            ]);
            $lesson->fill($this->translationPayload($validated));
            $lesson->save();

            $this->saveVideo($lesson, $validated, $lesson->videos()->first());
        });

        return ResponseAPI::success();
    }

    #[OA\Delete(
        path: '/admin/app/programs/{program}/lessons/{lesson}',
        summary: 'Xóa Lesson',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'lesson', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function destroy(Program $program, Lesson $lesson)
    {
        $lesson->delete();

        return ResponseAPI::success();
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
                'teacher_name' => $data['teacher_name'] ?? null,
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
