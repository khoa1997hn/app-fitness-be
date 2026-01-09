<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Http\Controllers\Controller;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Đăng nhập và lấy JWT token
     */
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
                null,
                401
            );
        }

        return $this->respondWithToken($token);
    }

    /**
     * Lấy thông tin user đang đăng nhập
     */
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        return ResponseAPI::success([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'dob' => $user->dob,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Đăng xuất (Invalidate token)
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return ResponseAPI::success(null, __('messages.logout_success'));
    }

    /**
     * Làm mới token
     */
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
