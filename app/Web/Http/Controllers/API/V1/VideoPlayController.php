<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\User;
use App\Share\Models\Video;
use App\Share\Services\Video\VideoPlayService;
use App\Share\Services\Video\VideoWatchProgressService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class VideoPlayController extends BaseAPIController
{
    public function __construct(
        private readonly VideoPlayService $videoPlayService,
        private readonly VideoWatchProgressService $videoWatchProgressService,
    ) {}

    /**
     * Lấy presigned URL và metadata video để phát.
     *
     * Presigned URL hết hạn sau `AWS_PRESIGNED_URL_EXPIRES` (phút). Khi URL hết hạn
     * hoặc player báo lỗi truy cập S3, app gọi lại **cùng endpoint này** — BE kiểm tra
     * quyền lại và trả `stream_url` mới (không có API refresh riêng).
     */
    #[OA\Post(
        path: '/videos/{video}/play',
        description: 'Kiểm tra subscription và quyền xem (plan, program đã chọn, loại bài). Trả metadata video + presigned GET stream_url + watched_percent của video, lesson và program. Khi URL hết hạn, client gọi lại endpoint này.',
        summary: 'Phát video bài học',
        security: [['bearerAuth' => []]],
        tags: ['Videos'],
        parameters: [
            new OA\Parameter(name: 'video', description: 'ID video', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cấp stream URL và metadata video thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'lesson_id', type: 'integer', example: 10),
                                new OA\Property(property: 'duration_seconds', type: 'integer', example: 600),
                                new OA\Property(
                                    property: 'file',
                                    properties: [
                                        new OA\Property(property: 'path', type: 'string'),
                                        new OA\Property(property: 'name', type: 'string'),
                                        new OA\Property(property: 'extension', type: 'string', example: 'mp4'),
                                        new OA\Property(property: 'size', type: 'integer', nullable: true),
                                        new OA\Property(property: 'url', type: 'string'),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(property: 'stream_url', type: 'string', example: 'https://s3.amazonaws.com/...'),
                                new OA\Property(
                                    property: 'progress',
                                    description: 'Tiến độ video này',
                                    properties: [
                                        new OA\Property(property: 'watched_seconds', type: 'integer', example: 120),
                                        new OA\Property(property: 'completed_percent', description: '0 hoặc 100', type: 'integer', example: 0),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'lesson',
                                    description: 'Tiến độ lesson chứa video này',
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
                                    description: 'Tiến độ program chứa video này',
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
            new OA\Response(response: 403, description: 'Không có quyền xem'),
            new OA\Response(response: 404, description: 'Video không tồn tại hoặc chưa có file'),
        ]
    )]
    public function play(Video $video): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $gate = $this->videoPlayService->streamGate($user, $video);

        if ($gate !== null) {
            return ResponseAPI::error($gate['message'], $gate['status']);
        }

        $streamUrl = $this->videoPlayService->createStreamUrl($video);

        if ($streamUrl === '') {
            return ResponseAPI::error(__('messages.video_file_not_available'), 404);
        }

        $video->loadMissing(['lesson.program']);
        $lesson = $video->lesson;
        $program = $lesson->program;

        return ResponseAPI::success([
            'id' => $video->id,
            'lesson_id' => $video->lesson_id,
            'duration_seconds' => (int) $video->duration_seconds,
            'file' => $video->file,
            'stream_url' => $streamUrl,
            'progress' => $this->videoWatchProgressService->videoProgress($user, $video),
            'lesson' => [
                'id' => $lesson->id,
                'progress' => $this->videoWatchProgressService->lessonProgress($user, $lesson),
            ],
            'program' => [
                'id' => $program->id,
                'progress' => $this->videoWatchProgressService->programProgress($user, $program),
            ],
        ]);
    }
}
