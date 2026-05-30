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
     * Danh sách program đã mua kèm thông tin subscription
     */
    #[OA\Get(
        path: '/programs/purchased',
        description: 'Lấy danh sách program đã mua của user kèm thông tin subscription (mọi trạng thái: active, cancelled, expired, ...). Trả started_at (created_at), renews_at (expires_at khi còn auto-renew), status. Plan All Access trả toàn bộ program.',
        summary: 'Danh sách program đã mua',
        security: [['bearerAuth' => []]],
        tags: ['Programs'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy danh sách thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'subscription',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 10),
                                        new OA\Property(property: 'plan', type: 'string', example: 'plus'),
                                        new OA\Property(property: 'status', type: 'string', example: 'active'),
                                        new OA\Property(property: 'provider', type: 'string', example: 'google_iap'),
                                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 1999000),
                                        new OA\Property(property: 'currency', type: 'string', example: 'VND'),
                                        new OA\Property(property: 'auto_renew', type: 'boolean', example: true),
                                        new OA\Property(property: 'started_at', type: 'string', format: 'date-time', example: '2026-01-01T00:00:00+07:00'),
                                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
                                        new OA\Property(property: 'renews_at', type: 'string', format: 'date-time', nullable: true),
                                        new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
                                        new OA\Property(property: 'show_plan_ends_notice', type: 'boolean', example: false),
                                        new OA\Property(property: 'can_cancel_renewal', type: 'boolean', example: true),
                                        new OA\Property(property: 'can_renew', type: 'boolean', example: false),
                                        new OA\Property(property: 'requires_selection', type: 'boolean', example: true),
                                        new OA\Property(property: 'max_programs', type: 'integer', example: 2, nullable: true),
                                        new OA\Property(
                                            property: 'allowed_lesson_types',
                                            type: 'array',
                                            items: new OA\Items(type: 'string', example: 'level')
                                        ),
                                    ],
                                    type: 'object',
                                    nullable: true
                                ),
                                new OA\Property(
                                    property: 'programs',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', type: 'string', example: 'Yoga'),
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
                                            new OA\Property(property: 'selected_at', type: 'string', format: 'date-time', nullable: true),
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
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function purchased(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return ResponseAPI::success($this->programSelectionService->getPurchased($user));
    }

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
