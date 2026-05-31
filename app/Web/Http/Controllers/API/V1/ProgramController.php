<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Services\Video\VideoWatchProgressService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;

class ProgramController extends BaseAPIController
{
    public function __construct(
        private readonly VideoWatchProgressService $videoWatchProgressService,
    ) {}

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
                                    new OA\Property(
                                        property: 'progress',
                                        description: 'Tiến độ hoàn thành program',
                                        properties: [
                                            new OA\Property(property: 'watched_seconds', type: 'integer', example: 1800),
                                            new OA\Property(property: 'completed_percent', type: 'integer', example: 30),
                                        ],
                                        type: 'object'
                                    ),
                                    new OA\Property(property: 'is_favorited', description: 'User hiện tại đã yêu thích program chưa', type: 'boolean', example: false),
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
        /** @var User $user */
        $user = auth()->user();

        $programs = Program::query()
            ->withTranslation()
            ->with($this->programRelations())
            ->orderByTranslation('sort')
            ->orderByDesc('id')
            ->get();

        $programIds = $programs->pluck('id')->all();
        $programProgressMap = $this->videoWatchProgressService->programProgressMapForUser($user, $programIds);
        $favoritedProgramIds = $user->favoritePrograms()->pluck('programs.id')->all();

        return ResponseAPI::success(
            $programs
                ->map(fn (Program $program) => [
                    ...$this->mapProgram($program),
                    'progress' => $programProgressMap[$program->id] ?? ['watched_seconds' => 0, 'completed_percent' => 0],
                    'is_favorited' => in_array($program->id, $favoritedProgramIds, true),
                ])
                ->toArray()
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
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Yoga'),
                                new OA\Property(property: 'description', type: 'string', nullable: true),
                                new OA\Property(
                                    property: 'cover',
                                    description: 'Ảnh cover program (theo locale)',
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
                                new OA\Property(property: 'rating', type: 'number', format: 'float', nullable: true),
                                new OA\Property(property: 'duration_minutes', type: 'integer', example: 30),
                                new OA\Property(property: 'course_count', type: 'integer', example: 3),
                                new OA\Property(
                                    property: 'goals',
                                    type: 'array',
                                    items: new OA\Items(type: 'string')
                                ),
                                new OA\Property(
                                    property: 'progress',
                                    description: 'Tiến độ hoàn thành program',
                                    properties: [
                                        new OA\Property(property: 'watched_seconds', type: 'integer', example: 1800),
                                        new OA\Property(property: 'completed_percent', type: 'integer', example: 20),
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
                                                            new OA\Property(property: 'day', description: 'Thứ tự ngày tập', type: 'integer', example: 1),
                                                            new OA\Property(property: 'name', type: 'string', example: 'Bài nhập môn'),
                                                            new OA\Property(property: 'description', type: 'string', nullable: true),
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
                                                                properties: [
                                                                    new OA\Property(property: 'watched_seconds', type: 'integer', example: 450),
                                                                    new OA\Property(property: 'completed_percent', type: 'integer', example: 50),
                                                                ],
                                                                type: 'object'
                                                            ),
                                                        ],
                                                        type: 'object'
                                                    )
                                                ),
                                                new OA\Property(
                                                    property: 'intermediate',
                                                    type: 'array',
                                                    items: new OA\Items(
                                                        properties: [
                                                            new OA\Property(property: 'id', type: 'integer', example: 2),
                                                            new OA\Property(property: 'day', description: 'Thứ tự ngày tập', type: 'integer', example: 2),
                                                            new OA\Property(property: 'name', type: 'string', example: 'Bài trung cấp'),
                                                            new OA\Property(property: 'description', type: 'string', nullable: true),
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
                                                            new OA\Property(property: 'duration_seconds', type: 'integer', example: 900),
                                                            new OA\Property(property: 'is_favorited', description: 'User hiện tại đã yêu thích bài học chưa', type: 'boolean', example: false),
                                                            new OA\Property(
                                                                property: 'progress',
                                                                properties: [
                                                                    new OA\Property(property: 'watched_seconds', type: 'integer', example: 450),
                                                                    new OA\Property(property: 'completed_percent', type: 'integer', example: 0),
                                                                ],
                                                                type: 'object'
                                                            ),
                                                        ],
                                                        type: 'object'
                                                    )
                                                ),
                                                new OA\Property(
                                                    property: 'advanced',
                                                    type: 'array',
                                                    items: new OA\Items(
                                                        properties: [
                                                            new OA\Property(property: 'id', type: 'integer', example: 3),
                                                            new OA\Property(property: 'day', description: 'Thứ tự ngày tập', type: 'integer', example: 3),
                                                            new OA\Property(property: 'name', type: 'string', example: 'Bài nâng cao'),
                                                            new OA\Property(property: 'description', type: 'string', nullable: true),
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
                                                            new OA\Property(property: 'duration_seconds', type: 'integer', example: 1200),
                                                            new OA\Property(property: 'is_favorited', description: 'User hiện tại đã yêu thích bài học chưa', type: 'boolean', example: false),
                                                            new OA\Property(
                                                                property: 'progress',
                                                                properties: [
                                                                    new OA\Property(property: 'watched_seconds', type: 'integer', example: 450),
                                                                    new OA\Property(property: 'completed_percent', type: 'integer', example: 0),
                                                                ],
                                                                type: 'object'
                                                            ),
                                                        ],
                                                        type: 'object'
                                                    )
                                                ),
                                            ],
                                            type: 'object'
                                        ),
                                        new OA\Property(
                                            property: 'special',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 4),
                                                    new OA\Property(property: 'day', description: 'Thứ tự ngày tập', type: 'integer', example: 1),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Bài đặc biệt'),
                                                    new OA\Property(property: 'description', type: 'string', nullable: true),
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
                                                        properties: [
                                                            new OA\Property(property: 'watched_seconds', type: 'integer', example: 450),
                                                            new OA\Property(property: 'completed_percent', type: 'integer', example: 0),
                                                        ],
                                                        type: 'object'
                                                    ),
                                                ],
                                                type: 'object'
                                            )
                                        ),
                                        new OA\Property(
                                            property: 'signature',
                                            type: 'array',
                                            items: new OA\Items(
                                                properties: [
                                                    new OA\Property(property: 'id', type: 'integer', example: 5),
                                                    new OA\Property(property: 'day', description: 'Thứ tự ngày tập', type: 'integer', example: 1),
                                                    new OA\Property(property: 'name', type: 'string', example: 'Bài signature'),
                                                    new OA\Property(property: 'description', type: 'string', nullable: true),
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
                                                        properties: [
                                                            new OA\Property(property: 'watched_seconds', type: 'integer', example: 450),
                                                            new OA\Property(property: 'completed_percent', type: 'integer', example: 0),
                                                        ],
                                                        type: 'object'
                                                    ),
                                                ],
                                                type: 'object'
                                            )
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
            new OA\Response(response: 404, description: 'Program không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function show(Program $program): JsonResponse
    {
        $program->load($this->programRelations());

        /** @var User $user */
        $user = auth()->user();

        $favoritedIds = $user->favoriteLessons()
            ->whereIn('lessons.id', $program->lessons->pluck('id'))
            ->pluck('lessons.id')
            ->all();

        $lessonIds = $program->lessons->pluck('id')->all();
        $lessonProgressMap = $this->videoWatchProgressService->lessonProgressMapForUser($user, $lessonIds);
        $programProgressData = $this->videoWatchProgressService->programProgress($user, $program);

        return ResponseAPI::success([
            ...$this->mapProgram($program),
            'progress' => $programProgressData,
            'lessons' => $this->groupLessons($program->lessons, $favoritedIds, $lessonProgressMap),
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
     * @param  list<int>  $favoritedIds
     * @param  array<int, array{watched_seconds: int, completed_percent: int}>  $lessonProgressMap
     * @return array<string, mixed>
     */
    private function mapLesson(Lesson $lesson, array $favoritedIds, array $lessonProgressMap): array
    {
        return [
            'id' => $lesson->id,
            'day' => $lesson->day,
            'name' => $lesson->name,
            'description' => $lesson->description,
            'thumbnail' => $lesson->thumbnail,
            'duration_seconds' => (int) $lesson->videos->sum('duration_seconds'),
            'is_favorited' => in_array($lesson->id, $favoritedIds, true),
            'progress' => $lessonProgressMap[$lesson->id] ?? ['watched_seconds' => 0, 'completed_percent' => 0],
        ];
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @param  list<int>  $favoritedIds
     * @param  array<int, array{watched_seconds: int, completed_percent: int}>  $lessonProgressMap
     * @return array<string, mixed>
     */
    private function groupLessons(Collection $lessons, array $favoritedIds, array $lessonProgressMap): array
    {
        $sorted = $this->sortLessons($lessons);

        $levelLessons = $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Level));

        return [
            'level' => [
                'beginner' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Beginner)),
                    $favoritedIds,
                    $lessonProgressMap
                ),
                'intermediate' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Intermediate)),
                    $favoritedIds,
                    $lessonProgressMap
                ),
                'advanced' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Advanced)),
                    $favoritedIds,
                    $lessonProgressMap
                ),
            ],
            'special' => $this->mapLessonsCollection(
                $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Special)),
                $favoritedIds,
                $lessonProgressMap
            ),
            'signature' => $this->mapLessonsCollection(
                $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Signature)),
                $favoritedIds,
                $lessonProgressMap
            ),
        ];
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @param  list<int>  $favoritedIds
     * @param  array<int, array{watched_seconds: int, completed_percent: int}>  $lessonProgressMap
     * @return list<array<string, mixed>>
     */
    private function mapLessonsCollection(Collection $lessons, array $favoritedIds, array $lessonProgressMap): array
    {
        return $lessons
            ->map(fn (Lesson $lesson) => $this->mapLesson($lesson, $favoritedIds, $lessonProgressMap))
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
            fn (Lesson $lesson) => $lesson->day,
            fn (Lesson $lesson) => $lesson->id,
        ])->values();
    }
}
