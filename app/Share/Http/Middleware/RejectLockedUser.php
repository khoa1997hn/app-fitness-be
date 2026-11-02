<?php

namespace App\Share\Http\Middleware;

use App\Share\Enums\UserStatus;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn cả token còn hạn của tài khoản đã khóa. Trả 401 để client đăng xuất.
 */
class RejectLockedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = auth('api')->user();

        if ($user?->status?->is(UserStatus::Locked)) {
            return ResponseAPI::error(__('messages.gym_account_locked'), 401);
        }

        return $next($request);
    }
}
