<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Models\User;
use App\Share\Services\Auth\ForgotPasswordService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use OpenApi\Attributes as OA;

class PasswordResetController extends BaseAPIController
{
    public function __construct(
        private readonly ForgotPasswordService $forgotPasswordService,
    ) {}

    /**
     * Gửi liên kết đặt lại mật khẩu qua email
     */
    #[OA\Post(
        path: '/auth/password/reset',
        description: 'Send a single-use password reset link by bilingual email. The current password is unchanged. Always returns success to avoid revealing whether the email exists.',
        summary: 'Forgot password — send reset link by email',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', description: 'Registered email address', type: 'string', format: 'email', maxLength: 255, example: 'user@example.com'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Request accepted (email sent only if account exists)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'If an account exists for this email, a password reset link has been sent.'),
                        new OA\Property(property: 'data', type: 'null', nullable: true),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $this->forgotPasswordService->sendResetLinkIfUserExists($validated['email']);

        return ResponseAPI::success(null, __('messages.password_reset_email_sent'));
    }

    #[OA\Post(
        path: '/auth/password/confirm',
        description: 'Validate the emailed token and save the new password. Tokens expire after 60 minutes and are consumed after a successful reset.',
        summary: 'Confirm password reset',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                new OA\Property(property: 'token', type: 'string'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, maxLength: 50),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Password updated', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Your password has been reset. Please sign in with your new password.'),
                new OA\Property(property: 'data', type: 'null', nullable: true),
            ])),
            new OA\Response(response: 422, description: 'Invalid input, invalid, expired or already used token'),
        ]
    )]
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'token' => 'required|string',
            'password' => 'required|string|min:8|max:50|confirmed',
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->update(['password' => $password]);
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return ResponseAPI::error(__('messages.password_reset_invalid'), 422);
        }

        return ResponseAPI::success(null, __('messages.password_reset_success'));
    }
}
