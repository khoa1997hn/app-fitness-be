<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Models\UserNotification;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymNotification',
    required: ['id', 'type', 'message', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 9),
        new OA\Property(property: 'type', type: 'string', enum: ['payment', 'schedule', 'package'], example: 'schedule'),
        new OA\Property(property: 'message', type: 'string', example: 'Yêu cầu Private Yoga lúc 15:00 20/11/2026 đã được duyệt.'),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class NotificationController extends BaseAPIController
{
    private const LIST_LIMIT = 100;

    #[OA\Get(
        path: '/users/me/notifications',
        description: '100 thông báo mới nhất của hội viên.',
        summary: 'Thông báo của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Account'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymNotification')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function index(): JsonResponse
    {
        $notifications = UserNotification::query()
            ->where('user_id', auth()->id())
            ->latest('id')
            ->limit(self::LIST_LIMIT)
            ->get();

        return ResponseAPI::success($notifications->map(fn (UserNotification $notification) => $this->map($notification))->all());
    }

    #[OA\Patch(
        path: '/notifications/{notification}/read',
        description: 'Đánh dấu đã đọc thông báo của chính mình.',
        summary: 'Đánh dấu đã đọc',
        security: [['bearerAuth' => []]],
        tags: ['Gym Account'],
        parameters: [new OA\Parameter(name: 'notification', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymNotification'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function read(int $notification): JsonResponse
    {
        /** @var UserNotification $item */
        $item = UserNotification::query()->where('user_id', auth()->id())->findOrFail($notification);

        if ($item->read_at === null) {
            $item->update(['read_at' => now()]);
        }

        return ResponseAPI::success($this->map($item));
    }

    /**
     * @return array<string, mixed>
     */
    private function map(UserNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'message' => $notification->message,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}
