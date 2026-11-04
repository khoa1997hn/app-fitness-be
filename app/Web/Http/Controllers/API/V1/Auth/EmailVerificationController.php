<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Services\Auth\EmailVerificationService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EmailVerificationController extends BaseAPIController
{
    public function __construct(private readonly EmailVerificationService $emailVerificationService) {}

    #[OA\Post(
        path: '/auth/email/verify',
        description: 'Xác thực email bằng token trong link đã gửi. Link có hiệu lực 24 giờ, bấm lại link đã dùng vẫn thành công.',
        summary: 'Xác thực email',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [new OA\Property(property: 'token', type: 'string')]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Đã xác thực'),
            new OA\Response(response: 422, description: 'Link không hợp lệ hoặc đã hết hạn'),
        ]
    )]
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => 'required|string']);

        if (! $this->emailVerificationService->verify($validated['token'])) {
            return ResponseAPI::error(__('messages.email_verify_invalid'), 422);
        }

        return ResponseAPI::success(message: __('messages.email_verified'));
    }

    #[OA\Post(
        path: '/auth/email/resend',
        description: 'Gửi lại email xác thực. Luôn trả thành công để không lộ email có tồn tại hay không. Tối đa 5 lần mỗi phút.',
        summary: 'Gửi lại email xác thực',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com')]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Đã nhận yêu cầu'),
            new OA\Response(response: 422, description: 'Email không hợp lệ'),
            new OA\Response(response: 429, description: 'Gửi quá nhiều lần'),
        ]
    )]
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => 'required|email']);

        $this->emailVerificationService->resend($validated['email']);

        return ResponseAPI::success(message: __('messages.email_verification_resent'));
    }
}
