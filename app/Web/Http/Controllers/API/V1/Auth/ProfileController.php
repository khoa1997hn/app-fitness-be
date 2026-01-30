<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProfileController extends BaseAPIController
{
    /**
     * Lấy thông tin user đang đăng nhập
     */
    #[OA\Get(
        path: '/api/v1/auth/profile',
        description: 'Trả về thông tin chi tiết của user hiện tại đang đăng nhập (dựa trên JWT token)',
        summary: 'Lấy thông tin profile của user đang đăng nhập',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy thông tin profile thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', description: 'ID của user', type: 'integer', example: 1),
                                new OA\Property(property: 'email', description: 'Email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'first_name', description: 'Tên', type: 'string', example: 'Nguyễn'),
                                new OA\Property(property: 'last_name', description: 'Họ', type: 'string', example: 'Văn A'),
                                new OA\Property(property: 'phone', description: 'Số điện thoại', type: 'string', example: '0123456789', nullable: true),
                                new OA\Property(property: 'dob', description: 'Ngày sinh', type: 'string', format: 'date', example: '1990-01-01', nullable: true),
                                new OA\Property(property: 'plan', description: 'Gói subscription hiện tại', type: 'string', enum: ['basic', 'plus', 'all'], example: 'all', nullable: true),
                                new OA\Property(property: 'subscription_status', description: 'Trạng thái subscription', type: 'string', enum: ['trial', 'active', 'expired', 'cancelled', 'grace_period', 'refunded'], example: 'active', nullable: true),
                                new OA\Property(property: 'created_at', description: 'Thời gian tạo', type: 'string', format: 'date-time', example: '2026-01-30T09:00:00.000000Z'),
                                new OA\Property(property: 'updated_at', description: 'Thời gian cập nhật', type: 'string', format: 'date-time', example: '2026-01-30T09:00:00.000000Z'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
        ]
    )]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return ResponseAPI::success([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'dob' => $user->dob,
            'plan' => $user->plan?->value,
            'subscription_status' => $user->subscription_status?->value,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }
}
