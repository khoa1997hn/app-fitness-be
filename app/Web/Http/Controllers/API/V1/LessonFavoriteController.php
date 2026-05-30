<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\Lesson;
use App\Share\Models\User;
use App\Share\Services\Video\VideoWatchProgressService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LessonFavoriteController extends BaseAPIController
{
    public function __construct(
        private readonly VideoWatchProgressService $videoWatchProgressService,
    ) {}

    /**
     * Danh sách bài học user đã yêu thích (flatten, phân trang)
     */
    #[OA\Get(
        path: '/lessons/favorites',
        description: 'Danh sách bài học user hiện tại đã yêu thích. Flatten theo bài học (không nhóm theo program), mỗi item kèm thông tin program. Sắp xếp mới yêu thích trước, có phân trang. Không trả link/file video.',
        summary: 'Danh sách bài học yêu thích',
        security: [['bearerAuth' => []]],
        tags: ['Lesson Favorites'],
        parameters: [
            new OA\Parameter(name: 'page', description: 'Trang', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', description: 'Số item mỗi trang (tối đa 50)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy danh sách bài học yêu thích thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'items',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 12),
                                            new OA\Property(property: 'name', description: 'Tên bài học (theo locale)', type: 'string', example: 'Day 1 - Warm up'),
                                            new OA\Property(
                                                property: 'thumbnail',
                                                description: 'Ảnh thumbnail (theo locale)',
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
                                            new OA\Property(property: 'day', description: 'Ngày tập của bài học', type: 'integer', example: 1),
                                            new OA\Property(property: 'duration_seconds', type: 'integer', example: 600),
                                            new OA\Property(property: 'is_favorited', type: 'boolean', example: true),
                                            new OA\Property(property: 'watched_percent', description: 'Phần trăm hoàn thành lesson (0-100)', type: 'integer', example: 50),
                                        ],
                                        type: 'object'
                                    )
                                ),
                                new OA\Property(
                                    property: 'pagination',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                                        new OA\Property(property: 'total', type: 'integer', example: 3),
                                        new OA\Property(property: 'last_page', type: 'integer', example: 1),
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
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $perPage = min((int) $request->integer('per_page', 10) ?: 10, 50);

        $favorites = $user->favoriteLessons()
            ->withTranslation()
            ->with(['videos' => fn ($query) => $query->withTranslation()])
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage);

        $lessons = collect($favorites->items());
        $lessonIds = $lessons->pluck('id')->all();
        $lessonPercentMap = $this->videoWatchProgressService->lessonPercentMapForUser($user, $lessonIds);

        return ResponseAPI::success([
            'items' => $lessons
                ->map(fn (Lesson $lesson) => $this->mapFavorite($lesson, $lessonPercentMap))
                ->all(),
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
                'last_page' => $favorites->lastPage(),
            ],
        ]);
    }

    /**
     * Yêu thích một bài học (idempotent)
     */
    #[OA\Post(
        path: '/lessons/{lesson}/favorite',
        description: 'Đánh dấu yêu thích một bài học cho user hiện tại. Idempotent: gọi lại khi đã yêu thích vẫn trả 200, không tạo bản ghi trùng.',
        summary: 'Yêu thích bài học',
        security: [['bearerAuth' => []]],
        tags: ['Lesson Favorites'],
        parameters: [
            new OA\Parameter(name: 'lesson', description: 'ID bài học', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đánh dấu yêu thích thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 404, description: 'Bài học không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function store(Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->favoriteLessons()->syncWithoutDetaching([$lesson->id]);

        return ResponseAPI::success();
    }

    /**
     * Bỏ yêu thích một bài học (idempotent)
     */
    #[OA\Delete(
        path: '/lessons/{lesson}/favorite',
        description: 'Bỏ yêu thích một bài học cho user hiện tại. Idempotent: gọi khi chưa yêu thích vẫn trả 200.',
        summary: 'Bỏ yêu thích bài học',
        security: [['bearerAuth' => []]],
        tags: ['Lesson Favorites'],
        parameters: [
            new OA\Parameter(name: 'lesson', description: 'ID bài học', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bỏ yêu thích thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 404, description: 'Bài học không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function destroy(Lesson $lesson): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->favoriteLessons()->detach($lesson->id);

        return ResponseAPI::success();
    }

    /**
     * @param  array<int, int>  $lessonPercentMap  lessonId => watched_percent
     * @return array<string, mixed>
     */
    private function mapFavorite(Lesson $lesson, array $lessonPercentMap): array
    {
        return [
            'id' => $lesson->id,
            'name' => $lesson->name,
            'thumbnail' => $lesson->thumbnail,
            'day' => $lesson->day,
            'duration_seconds' => (int) $lesson->videos->sum('duration_seconds'),
            'is_favorited' => true,
            'watched_percent' => $lessonPercentMap[$lesson->id] ?? 0,
        ];
    }
}
