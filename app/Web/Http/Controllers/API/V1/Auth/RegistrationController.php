<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RegistrationController extends BaseAPIController
{
    /**
     * Đăng ký user mới
     */
    #[OA\Post(
        path: '/auth/register',
        description: 'Tạo tài khoản user mới với thông tin đăng ký. Sau khi đăng ký thành công, user cần đăng nhập để lấy JWT token.',
        summary: 'Đăng ký tài khoản mới',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'first_name', 'last_name', 'dob'],
                properties: [
                    new OA\Property(property: 'email', description: 'Email đăng ký (phải unique)', type: 'string', format: 'email', maxLength: 255, example: 'user@example.com'),
                    new OA\Property(property: 'password', description: 'Mật khẩu (tối thiểu 8 ký tự, tối đa 50 ký tự)', type: 'string', format: 'password', maxLength: 50, minLength: 8, example: 'password123'),
                    new OA\Property(property: 'first_name', description: 'Tên', type: 'string', maxLength: 255, example: 'Nguyễn'),
                    new OA\Property(property: 'last_name', description: 'Họ', type: 'string', maxLength: 255, example: 'Văn A'),
                    new OA\Property(property: 'phone', description: 'Số điện thoại (tùy chọn)', type: 'string', maxLength: 255, example: '0123456789', nullable: true),
                    new OA\Property(property: 'dob', description: 'Ngày sinh (định dạng Y-m-d)', type: 'string', format: 'date', example: '1990-01-01'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Đăng ký thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đăng ký thành công'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', description: 'ID của user', type: 'integer', example: 1),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'first_name', type: 'string', example: 'Nguyễn'),
                                new OA\Property(property: 'last_name', type: 'string', example: 'Văn A'),
                                new OA\Property(property: 'phone', type: 'string', example: '0123456789', nullable: true),
                                new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1990-01-01', nullable: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-01-30T09:00:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2026-01-30T09:00:00.000000Z'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['email' => ['The email has already been taken.']],
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'dob' => 'required|date:Y-m-d',
        ]);

        $user = User::query()->create([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'dob' => $validated['dob'],
        ]);

        return ResponseAPI::success([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'dob' => $user->dob,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], __('messages.registration_success'), 201);
    }
}
