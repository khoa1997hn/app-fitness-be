<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\Combo;
use App\Share\Models\ComboInfo;
use App\Share\Models\User;
use App\Share\Services\Video\VideoWatchProgressService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Concerns\MapsProgramForApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;

class ComboController extends BaseAPIController
{
    use MapsProgramForApi;

    public function __construct(
        private readonly VideoWatchProgressService $videoWatchProgressService,
    ) {}

    #[OA\Get(
        path: '/combos',
        description: 'Lấy danh sách combo. Không phân trang. Combo chứa program user đã yêu thích hiển thị trước (favorite mới nhất trước), còn lại theo id giảm dần.',
        summary: 'Lấy danh sách combo',
        security: [['bearerAuth' => []]],
        tags: ['Combos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy danh sách combo thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Yoga & Pilates'),
                                    new OA\Property(
                                        property: 'cover',
                                        properties: [
                                            new OA\Property(property: 'path', type: 'string', example: 'combo/cover/sample.jpg'),
                                            new OA\Property(property: 'name', type: 'string', example: 'sample.jpg'),
                                            new OA\Property(property: 'extension', type: 'string', example: 'jpg', nullable: true),
                                            new OA\Property(property: 'size', type: 'integer', example: 102400, nullable: true),
                                            new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/combo/cover/sample.jpg'),
                                        ],
                                        type: 'object',
                                        nullable: true
                                    ),
                                    new OA\Property(property: 'program_count', type: 'integer', example: 3),
                                    new OA\Property(
                                        property: 'infos',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(
                                                    property: 'icon',
                                                    properties: [
                                                        new OA\Property(property: 'path', type: 'string', example: 'combo/info-icon/icon.png'),
                                                        new OA\Property(property: 'name', type: 'string', example: 'icon.png'),
                                                        new OA\Property(property: 'extension', type: 'string', example: 'png', nullable: true),
                                                        new OA\Property(property: 'size', type: 'integer', example: 1024, nullable: true),
                                                        new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/combo/info-icon/icon.png'),
                                                    ],
                                                    type: 'object'
                                                ),
                                                new OA\Property(property: 'text', type: 'string', example: 'Giảm stress'),
                                            ],
                                            type: 'object'
                                        )
                                    ),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $favoriteTimestamps = $user->favoritePrograms()
            ->get(['programs.id'])
            ->keyBy('id')
            ->map(fn ($program) => Carbon::parse($program->pivot->created_at));

        $combos = Combo::query()
            ->withTranslation()
            ->with([
                'infos' => fn ($query) => $query->withTranslation(),
                'programs',
            ])
            ->withCount('programs')
            ->get();

        $sorted = $this->sortCombosByFavoritePrograms($combos, $favoriteTimestamps);

        return ResponseAPI::success(
            $sorted->map(fn (Combo $combo) => $this->mapComboListItem($combo))->values()->all()
        );
    }

    #[OA\Get(
        path: '/combos/{combo}',
        description: 'Lấy chi tiết combo kèm danh sách program full detail (giống GET /programs/{program}). Programs sort theo thứ tự trong combo.',
        summary: 'Chi tiết combo',
        security: [['bearerAuth' => []]],
        tags: ['Combos'],
        parameters: [
            new OA\Parameter(
                name: 'combo',
                description: 'ID combo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy chi tiết combo thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Yoga & Pilates'),
                                new OA\Property(property: 'program_count', type: 'integer', example: 2),
                                new OA\Property(
                                    property: 'programs',
                                    type: 'array',
                                    items: new OA\Items(type: 'object', description: 'Full program detail — xem GET /programs/{program}')
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 404, description: 'Combo không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function show(Combo $combo): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $combo->load([
            'infos' => fn ($query) => $query->withTranslation(),
            'programs' => fn ($query) => $query->withTranslation()
                ->with($this->programRelations()),
        ]);

        return ResponseAPI::success([
            ...$this->mapComboListItem($combo),
            'programs' => $combo->programs
                ->map(fn ($program) => $this->mapProgramDetail($program, $user, $this->videoWatchProgressService))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapComboListItem(Combo $combo): array
    {
        return [
            'id' => $combo->id,
            'name' => $combo->name,
            'cover' => $combo->cover,
            'program_count' => $combo->programs_count ?? $combo->programs->count(),
            'infos' => $combo->infos
                ->map(fn (ComboInfo $info) => [
                    'icon' => $info->icon,
                    'text' => $info->text,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Combo>  $combos
     * @param  Collection<int, Carbon>  $favoriteTimestamps  program_id => favorited_at
     * @return Collection<int, Combo>
     */
    private function sortCombosByFavoritePrograms(Collection $combos, Collection $favoriteTimestamps): Collection
    {
        return $combos->sort(function (Combo $a, Combo $b) use ($favoriteTimestamps) {
            $aFavoriteAt = $this->latestFavoriteAtInCombo($a, $favoriteTimestamps);
            $bFavoriteAt = $this->latestFavoriteAtInCombo($b, $favoriteTimestamps);

            if ($aFavoriteAt && ! $bFavoriteAt) {
                return -1;
            }

            if (! $aFavoriteAt && $bFavoriteAt) {
                return 1;
            }

            if ($aFavoriteAt && $bFavoriteAt) {
                return $bFavoriteAt <=> $aFavoriteAt;
            }

            return $b->id <=> $a->id;
        })->values();
    }

    /**
     * @param  Collection<int, Carbon>  $favoriteTimestamps
     */
    private function latestFavoriteAtInCombo(Combo $combo, Collection $favoriteTimestamps): ?Carbon
    {
        $timestamps = $combo->programs
            ->map(fn ($program) => $favoriteTimestamps->get($program->id))
            ->filter()
            ->sortDesc()
            ->values();

        return $timestamps->first();
    }
}
