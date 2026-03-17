<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends BaseAPIController
{
    /**
     * Đăng nhập và lấy JWT token
     */
    #[OA\Post(
        path: '/auth/login',
        description: 'Xác thực thông tin đăng nhập của user và trả về JWT access token để sử dụng cho các request tiếp theo',
        summary: 'Đăng nhập và lấy JWT token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', description: 'Email đăng nhập', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', description: 'Mật khẩu đăng nhập', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đăng nhập thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đăng nhập thành công'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'access_token', description: 'JWT access token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                                new OA\Property(property: 'token_type', description: 'Loại token', type: 'string', example: 'bearer'),
                                new OA\Property(property: 'expires_in', description: 'Thời gian hết hạn token (giây)', type: 'integer', example: 3600),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Thông tin đăng nhập không đúng',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Thông tin đăng nhập không đúng'),
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
                            example: ['email' => ['The email field is required.']],
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
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! $token = auth('api')->attempt($credentials)) {
            return ResponseAPI::error(
                __('messages.login_failed'),
                401
            );
        }

        return $this->respondWithToken($token);
    }

    /**
     * Đăng xuất (Invalidate token)
     */
    #[OA\Post(
        path: '/auth/logout',
        description: 'Vô hiệu hóa JWT token hiện tại của user, yêu cầu đăng nhập lại để lấy token mới',
        summary: 'Đăng xuất',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đăng xuất thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Đăng xuất thành công'),
                        new OA\Property(property: 'data', type: 'null', nullable: true),
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
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return ResponseAPI::success(null, __('messages.logout_success'));
    }

    /**
     * Làm mới token
     */
    #[OA\Post(
        path: '/auth/refresh',
        description: 'Tạo JWT token mới dựa trên token hiện tại, token cũ sẽ bị vô hiệu hóa',
        summary: 'Làm mới JWT token',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Làm mới token thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Làm mới token thành công'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'access_token', description: 'JWT access token mới', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'),
                                new OA\Property(property: 'token_type', description: 'Loại token', type: 'string', example: 'bearer'),
                                new OA\Property(property: 'expires_in', description: 'Thời gian hết hạn token (giây)', type: 'integer', example: 3600),
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
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(auth('api')->refresh(), __('messages.refresh_success'));
    }

    /**
     * Trả về response với token
     */
    protected function respondWithToken(string $token, ?string $message = null): JsonResponse
    {
        return ResponseAPI::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ], $message ?? __('messages.login_success'));
    }
}
