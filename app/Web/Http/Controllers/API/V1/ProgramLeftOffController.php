<?php

declare(strict_types=1);

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Services\Video\VideoWatchProgressService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProgramLeftOffController extends BaseAPIController
{
    public function __construct(
        private readonly VideoWatchProgressService $videoWatchProgressService,
    ) {}

    #[OA\Get(
        path: '/programs/left-off',
        description: 'Trả về danh sách tất cả programs user đã có watch progress, sort theo last_watched_at mới nhất. Mỗi item kèm last_lesson và video cụ thể. Nếu user chưa xem video nào, data = [].',
        summary: 'Danh sách programs đã và đang học',
        security: [['bearerAuth' => []]],
        tags: ['Programs'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            description: 'Danh sách programs user đã xem, sort theo last_watched_at mới nhất. Rỗng nếu user chưa xem video nào.',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Mat Pilates'),
                                    new OA\Property(
                                        property: 'cover',
                                        description: 'Ảnh cover của program (theo locale)',
                                        properties: [
                                            new OA\Property(property: 'path', type: 'string', example: 'programs/cover/sample.jpg'),
                                            new OA\Property(property: 'name', type: 'string', example: 'sample.jpg'),
                                            new OA\Property(property: 'extension', type: 'string', example: 'jpg', nullable: true),
                                            new OA\Property(property: 'size', type: 'integer', example: 102400, nullable: true),
                                            new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/programs/cover/sample.jpg'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(property: 'duration_seconds', description: 'Tổng duration (giây) của toàn bộ videos trong program', type: 'integer', example: 1800),
                                    new OA\Property(
                                        property: 'progress',
                                        description: 'Tiến độ hoàn thành program',
                                        properties: [
                                            new OA\Property(property: 'watched_seconds', type: 'integer', example: 360),
                                            new OA\Property(property: 'completed_percent', type: 'integer', example: 20),
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(
                                        property: 'last_lesson',
                                        description: 'Bài học chứa video được xem gần nhất',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 12),
                                            new OA\Property(property: 'name', type: 'string', example: 'Glutes & Core'),
                                            new OA\Property(property: 'day', description: 'Ngày thứ N trong program', type: 'integer', example: 12),
                                        ],
                                        type: 'object'
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        /** @var \App\Share\Models\User $user */
        $user = auth()->user();

        return ResponseAPI::success($this->videoWatchProgressService->leftOffProgram($user));
    }
}
