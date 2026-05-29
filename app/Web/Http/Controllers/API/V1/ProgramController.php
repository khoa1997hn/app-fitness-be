<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\Program;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProgramController extends BaseAPIController
{
    /**
     * Lấy danh sách program cho màn Home
     */
    #[OA\Get(
        path: '/programs',
        description: 'Lấy danh sách program (bộ môn) hiển thị ở màn Home. Trả full list (không phân trang), sắp xếp theo sort tăng dần và id giảm dần. duration_minutes và course_count được tính động từ lessons/videos. Không trả link xem video.',
        summary: 'Lấy danh sách program',
        security: [['bearerAuth' => []]],
        tags: ['Programs'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy danh sách program thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', description: 'ID của program', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', description: 'Tên program (theo locale)', type: 'string', example: 'Pilates'),
                                    new OA\Property(property: 'description', description: 'Mô tả (theo locale)', type: 'string', example: 'Mô tả chương trình Pilates.', nullable: true),
                                    new OA\Property(
                                        property: 'cover',
                                        description: 'Ảnh cover (theo locale)',
                                        properties: [
                                            new OA\Property(property: 'path', description: 'Đường dẫn file', type: 'string', example: 'programs/cover/sample.jpg'),
                                            new OA\Property(property: 'name', description: 'Tên file', type: 'string', example: 'sample.jpg'),
                                            new OA\Property(property: 'extension', description: 'Phần mở rộng file', type: 'string', example: 'jpg', nullable: true),
                                            new OA\Property(property: 'size', description: 'Kích thước file (bytes)', type: 'integer', example: 102400, nullable: true),
                                            new OA\Property(property: 'url', description: 'URL đầy đủ', type: 'string', example: 'http://localhost/storage/programs/cover/sample.jpg'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(property: 'rating', description: 'Đánh giá (admin nhập)', type: 'number', format: 'float', example: 4.9, nullable: true),
                                    new OA\Property(property: 'duration_minutes', description: 'Tổng thời lượng (phút), tính từ video', type: 'integer', example: 30),
                                    new OA\Property(property: 'course_count', description: 'Số lượng bài học', type: 'integer', example: 12),
                                    new OA\Property(
                                        property: 'goals',
                                        description: 'Danh sách lợi ích (theo locale)',
                                        type: 'array',
                                        items: new OA\Items(type: 'string', example: 'Cải thiện sức khỏe')
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực (thiếu hoặc sai token)'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function index(): JsonResponse
    {
        $programs = Program::query()
            ->withTranslation()
            ->with([
                'lessons.videos' => fn ($query) => $query->withTranslation(),
                'goals' => fn ($query) => $query->withTranslation()->orderBy('sort'),
            ])
            ->orderByTranslation('sort')
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success(
            $programs->map(function (Program $program) {
                $totalSeconds = $program->lessons
                    ->sum(fn ($lesson) => $lesson->videos->sum('duration_seconds'));

                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'description' => $program->description,
                    'cover' => $program->cover,
                    'rating' => $program->rating,
                    'duration_minutes' => (int) round($totalSeconds / 60),
                    'course_count' => $program->lessons->count(),
                    'goals' => $program->goals->map(fn ($goal) => $goal->content)->all(),
                ];
            })->toArray()
        );
    }
}
