<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\User;
use App\Share\Services\Program\ProgramSelectionService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProgramSelectionController extends BaseAPIController
{
    public function __construct(
        private readonly ProgramSelectionService $programSelectionService,
    ) {}

    /**
     * Trạng thái chọn program theo gói subscription hiện tại
     */
    #[OA\Get(
        path: '/programs/selection',
        description: 'Lấy trạng thái chọn program của user theo subscription đang hợp lệ (trial/active/grace_period): subscription_id, plan, giới hạn số program, lesson types được phép, danh sách program đã chọn.',
        summary: 'Trạng thái chọn program',
        security: [['bearerAuth' => []]],
        tags: ['Programs'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy trạng thái thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'subscription_id', type: 'integer', example: 10),
                                new OA\Property(property: 'plan', type: 'string', example: 'plus'),
                                new OA\Property(property: 'requires_selection', type: 'boolean', example: true),
                                new OA\Property(property: 'max_programs', type: 'integer', example: 2, nullable: true),
                                new OA\Property(
                                    property: 'allowed_lesson_types',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'level')
                                ),
                                new OA\Property(
                                    property: 'selected_programs',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'Yoga'),
                                            new OA\Property(property: 'selected_at', type: 'string', format: 'date-time', example: '2026-05-29T10:00:00+07:00'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 403, description: 'Không có subscription hợp lệ'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->validSubscription === null) {
            return ResponseAPI::error(__('messages.no_active_subscription'), 403);
        }

        return ResponseAPI::success($this->programSelectionService->getStatus($user));
    }

    /**
     * Xác nhận / cập nhật program đã chọn
     */
    #[OA\Post(
        path: '/programs/selection',
        description: 'Chọn program theo gói subscription hiện tại. Replace toàn bộ selection cũ. Basic: tối đa 1 program. Plus: tối đa 2. All Access: không cho phép (422).',
        summary: 'Chọn program',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['program_ids'],
                properties: [
                    new OA\Property(
                        property: 'program_ids',
                        description: 'Danh sách ID program (unique)',
                        type: 'array',
                        items: new OA\Items(type: 'integer', example: 1),
                        example: [1, 2]
                    ),
                ]
            )
        ),
        tags: ['Programs'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chọn program thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', description: 'Cùng shape GET /programs/selection', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 403, description: 'Không có subscription hợp lệ'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->validSubscription === null) {
            return ResponseAPI::error(__('messages.no_active_subscription'), 403);
        }

        $validated = $request->validate([
            'program_ids' => ['required', 'array', 'min:1'],
            'program_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        return ResponseAPI::success(
            $this->programSelectionService->syncSelection($user, $validated['program_ids'])
        );
    }
}
