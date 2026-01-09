<?php

use App\Share\Utils\ResponseAPI;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Xử lý exception cho API requests
        $exceptions->render(function (Throwable $e, Request $request) {
            // Chỉ xử lý cho các request API
            if (! $request->is('api/*')) {
                return null;
            }

            // ValidationException (422)
            if ($e instanceof ValidationException) {
                return ResponseAPI::error(
                    __('messages.validation_error'),
                    $e->errors(),
                    422
                );
            }

            // AuthenticationException (401)
            if ($e instanceof AuthenticationException) {
                return ResponseAPI::error(
                    __('messages.authentication_error'),
                    null,
                    401
                );
            }

            // AuthorizationException hoặc AccessDeniedHttpException (403)
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException || $e instanceof AccessDeniedHttpException) {
                return ResponseAPI::error(
                    __('messages.authorization_error'),
                    null,
                    403
                );
            }

            // ModelNotFoundException hoặc NotFoundHttpException (404)
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ResponseAPI::error(
                    __('messages.not_found_error'),
                    null,
                    404
                );
            }

            // Các exception khác (500)
            return ResponseAPI::error(
                config('app.debug') ? $e->getMessage() : __('messages.server_error'),
                config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ] : null,
                500
            );
        });
    })->create();
