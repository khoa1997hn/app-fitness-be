<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\Lesson;
use App\Share\Models\User;
use App\Share\Services\Video\VideoWatchProgressService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Concerns\MapsLessonForApi;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class LessonController extends BaseAPIController
{
    use MapsLessonForApi;

    public function __construct(
        private readonly VideoWatchProgressService $videoWatchProgressService,
    ) {}

    /**
     * Chi tiết một bài học
     */
    #[OA\Get(
        path: '/lessons/{lesson}',
        description: 'Lấy thông tin chi tiết một bài học. Không trả link/file video — dùng POST /videos/{video}/play để phát.',
        summary: 'Chi tiết bài học',
        security: [['bearerAuth' => []]],
        tags: ['Lessons'],
        parameters: [
            new OA\Parameter(name: 'lesson', description: 'ID bài học', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy chi tiết bài học thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 12),
                                new OA\Property(property: 'video_id', description: 'ID video để gọi POST /videos/{video}/play', type: 'integer', example: 10, nullable: true),
                                new OA\Property(property: 'day', description: 'Thứ tự ngày tập', type: 'integer', example: 1),
                                new OA\Property(property: 'name', description: 'Tên bài học (theo locale)', type: 'string', example: 'Day 1 - Warm up'),
                                new OA\Property(property: 'description', description: 'Mô tả (theo locale)', type: 'string', nullable: true),
                                new OA\Property(property: 'teacher_name', description: 'Tên giáo viên (theo locale)', type: 'string', example: 'Jane Doe', nullable: true),
                                new OA\Property(
                                    property: 'thumbnail',
                                    description: 'Ảnh thumbnail bài học (theo locale)',
                                    properties: [
                                        new OA\Property(property: 'path', description: 'Đường dẫn file', type: 'string', example: 'lessons/thumbnails/lesson.jpg'),
                                        new OA\Property(property: 'name', description: 'Tên file', type: 'string', example: 'lesson.jpg'),
                                        new OA\Property(property: 'extension', description: 'Phần mở rộng file', type: 'string', example: 'jpg', nullable: true),
                                        new OA\Property(property: 'size', description: 'Kích thước file (bytes)', type: 'integer', example: 102400, nullable: true),
                                        new OA\Property(property: 'url', description: 'URL đầy đủ', type: 'string', example: 'http://localhost/storage/lessons/thumbnails/lesson.jpg'),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(property: 'duration_seconds', type: 'integer', example: 600),
                                new OA\Property(property: 'is_favorited', description: 'User hiện tại đã yêu thích bài học chưa', type: 'boolean', example: false),
                                new OA\Property(
                                    property: 'progress',
                                    description: 'Tiến độ hoàn thành lesson',
                                    properties: [
                                        new OA\Property(property: 'watched_seconds', type: 'integer', example: 450),
                                        new OA\Property(property: 'completed_percent', type: 'integer', example: 50),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 404, description: 'Bài học không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function show(Lesson $lesson): JsonResponse
    {
        $lesson->load([
            'videos' => fn ($query) => $query->withTranslation(),
        ]);

        /** @var User $user */
        $user = auth()->user();

        $isFavorited = $user->favoriteLessons()
            ->where('lessons.id', $lesson->id)
            ->exists();

        $lessonProgressMap = $this->videoWatchProgressService->lessonProgressMapForUser($user, [$lesson->id]);

        return ResponseAPI::success($this->mapLessonForApi($lesson, $isFavorited, $lessonProgressMap));
    }
}
