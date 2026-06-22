<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Services\Auth\ForgotPasswordService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PasswordResetController extends BaseAPIController
{
    public function __construct(
        private readonly ForgotPasswordService $forgotPasswordService,
    ) {}

    /**
     * Gửi mật khẩu mới qua email
     */
    #[OA\Post(
        path: '/auth/password/reset',
        description: 'Request a new random password sent to the user email. Always returns success to avoid revealing whether the email exists. Email content is in English.',
        summary: 'Forgot password — send new password by email',
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
                        new OA\Property(property: 'message', type: 'string', example: 'If an account exists for this email, a new password has been sent.'),
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

        $this->forgotPasswordService->sendNewPasswordIfUserExists($validated['email']);

        return ResponseAPI::success(null, __('messages.password_reset_email_sent'));
    }
}
