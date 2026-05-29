<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
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
            ->with($this->programRelations())
            ->orderByTranslation('sort')
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success(
            $programs->map(fn (Program $program) => $this->mapProgram($program))->toArray()
        );
    }

    /**
     * Chi tiết program + bài học nhóm theo level / special / signature
     */
    #[OA\Get(
        path: '/programs/{program}',
        description: 'Lấy chi tiết một program kèm danh sách bài học nhóm theo level (beginner/intermediate/advanced), special và signature. Mỗi bài học trả id, name, description, duration_seconds — không trả file/url video. Program không tồn tại → 404 (route model binding).',
        summary: 'Chi tiết program',
        security: [['bearerAuth' => []]],
        tags: ['Programs'],
        parameters: [
            new OA\Parameter(
                name: 'program',
                description: 'ID program',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy chi tiết program thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'program',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Yoga'),
                                        new OA\Property(property: 'description', type: 'string', nullable: true),
                                        new OA\Property(property: 'cover', type: 'object', nullable: true),
                                        new OA\Property(property: 'rating', type: 'number', format: 'float', nullable: true),
                                        new OA\Property(property: 'duration_minutes', type: 'integer', example: 30),
                                        new OA\Property(property: 'course_count', type: 'integer', example: 3),
                                        new OA\Property(
                                            property: 'goals',
                                            type: 'array',
                                            items: new OA\Items(type: 'string')
                                        ),
                                    ],
                                    type: 'object'
                                ),
                                new OA\Property(
                                    property: 'lessons',
                                    properties: [
                                        new OA\Property(
                                            property: 'level',
                                            properties: [
                                                new OA\Property(
                                                    property: 'beginner',
                                                    type: 'array',
                                                    items: new OA\Items(
                                                        properties: [
                                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                                            new OA\Property(property: 'name', type: 'string', example: 'Bài nhập môn'),
                                                            new OA\Property(property: 'description', type: 'string', nullable: true),
                                                            new OA\Property(property: 'duration_seconds', type: 'integer', example: 600),
                                                        ],
                                                        type: 'object'
                                                    )
                                                ),
                                                new OA\Property(property: 'intermediate', type: 'array', items: new OA\Items(type: 'object')),
                                                new OA\Property(property: 'advanced', type: 'array', items: new OA\Items(type: 'object')),
                                            ],
                                            type: 'object'
                                        ),
                                        new OA\Property(property: 'special', type: 'array', items: new OA\Items(type: 'object')),
                                        new OA\Property(property: 'signature', type: 'array', items: new OA\Items(type: 'object')),
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
            new OA\Response(response: 404, description: 'Program không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function show(Program $program): JsonResponse
    {
        $program->load($this->programRelations());

        return ResponseAPI::success([
            'program' => $this->mapProgram($program),
            'lessons' => $this->groupLessons($program->lessons),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function programRelations(): array
    {
        return [
            'lessons' => fn ($query) => $query->withTranslation()
                ->with(['videos' => fn ($videoQuery) => $videoQuery->withTranslation()]),
            'goals' => fn ($query) => $query->withTranslation()->orderBy('sort'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProgram(Program $program): array
    {
        $totalSeconds = $program->lessons
            ->sum(fn (Lesson $lesson) => $lesson->videos->sum('duration_seconds'));

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
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLesson(Lesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'name' => $lesson->name,
            'description' => $lesson->description,
            'duration_seconds' => (int) $lesson->videos->sum('duration_seconds'),
        ];
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return array<string, mixed>
     */
    private function groupLessons(Collection $lessons): array
    {
        $sorted = $this->sortLessons($lessons);

        $levelLessons = $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Level));

        return [
            'level' => [
                'beginner' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Beginner))
                ),
                'intermediate' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Intermediate))
                ),
                'advanced' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Advanced))
                ),
            ],
            'special' => $this->mapLessonsCollection(
                $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Special))
            ),
            'signature' => $this->mapLessonsCollection(
                $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Signature))
            ),
        ];
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return list<array<string, mixed>>
     */
    private function mapLessonsCollection(Collection $lessons): array
    {
        return $lessons
            ->map(fn (Lesson $lesson) => $this->mapLesson($lesson))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return Collection<int, Lesson>
     */
    private function sortLessons(Collection $lessons): Collection
    {
        return $lessons->sortBy([
            fn (Lesson $lesson) => $lesson->name ?? '',
            fn (Lesson $lesson) => $lesson->id,
        ])->values();
    }
}
