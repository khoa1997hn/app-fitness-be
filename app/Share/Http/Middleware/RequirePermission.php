<?php

namespace App\Share\Http\Middleware;

use App\Share\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user?->hasPermission($permission)) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
