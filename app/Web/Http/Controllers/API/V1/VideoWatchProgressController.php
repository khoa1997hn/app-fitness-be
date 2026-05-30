<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Http\Requests\VideoWatchProgressRequest;
use App\Share\Models\User;
use App\Share\Models\Video;
use App\Share\Services\Video\VideoWatchProgressService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class VideoWatchProgressController extends BaseAPIController
{
    public function __construct(
        private readonly VideoWatchProgressService $videoWatchProgressService,
    ) {}

    /**
     * Ghi tiến độ xem video.
     *
     * App gọi định kỳ (start / heartbeat / end).
     * Truyền số giây đã xem và flag hoàn thành (FE tự quyết).
     * Trả progress object cho video + lesson + program.
     */
    #[OA\Post(
        path: '/videos/{video}/watch-progress',
        description: 'Lưu tiến độ xem video cho user đăng nhập. watched_seconds chỉ tăng (max). is_completed một khi true không giảm. Trả progress object cho video, lesson, program.',
        summary: 'Cập nhật tiến độ xem video',
        security: [['bearerAuth' => []]],
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(name: 'video', description: 'ID video', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['watched_seconds', 'is_completed'],
                properties: [
                    new OA\Property(property: 'watched_seconds', description: 'Số giây đã xem (≥ 0)', type: 'integer', example: 120),
                    new OA\Property(property: 'is_completed', description: 'Flag hoàn thành do FE tự quyết', type: 'boolean', example: false),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lưu tiến độ thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'video',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 5),
                                        new OA\Property(property: 'lesson_id', type: 'integer', example: 10),
                                        new OA\Property(property: 'duration_seconds', type: 'integer', example: 600),
                                        new OA\Property(
                                            property: 'progress',
                                            properties: [
                                                new OA\Property(property: 'watched_seconds', type: 'integer', example: 120),
                                                new OA\Property(property: 'completed_percent', description: '0 hoặc 100 cho từng video', type: 'integer', example: 0),
                                            ],
                                            type: 'object'
                                        ),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'lesson',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 10),
                                        new OA\Property(
                                            property: 'progress',
                                            properties: [
                                                new OA\Property(property: 'watched_seconds', type: 'integer', example: 450),
                                                new OA\Property(property: 'completed_percent', type: 'integer', example: 25),
                                            ],
                                            type: 'object'
                                        ),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'program',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(
                                            property: 'progress',
                                            properties: [
                                                new OA\Property(property: 'watched_seconds', type: 'integer', example: 1800),
                                                new OA\Property(property: 'completed_percent', type: 'integer', example: 12),
                                            ],
                                            type: 'object'
                                        ),
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
            new OA\Response(response: 404, description: 'Video không tồn tại'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(VideoWatchProgressRequest $request, Video $video): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $payload = $this->videoWatchProgressService->record(
            $user,
            $video,
            (int) $request->integer('watched_seconds'),
            (bool) $request->boolean('is_completed'),
        );

        return ResponseAPI::success($payload);
    }
}
