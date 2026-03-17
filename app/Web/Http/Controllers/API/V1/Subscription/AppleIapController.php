<?php

namespace App\Web\Http\Controllers\API\V1\Subscription;

use App\Share\Exceptions\Subscription\InvalidReceiptException;
use App\Share\Services\Subscription\AppleService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AppleIapController extends BaseAPIController
{
    public function __construct(
        protected AppleService $appleService
    ) {}

    /**
     * Verify Apple App Store purchase
     */
    #[OA\Post(
        path: '/subscriptions/iap/apple/verify',
        description: 'Xác thực receipt từ Apple App Store và tạo/cập nhật subscription cho user. Hỗ trợ các gói: basic, plus, all.',
        summary: 'Xác thực giao dịch Apple App Store',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['receipt'],
                properties: [
                    new OA\Property(property: 'receipt', description: 'Receipt data từ Apple App Store (base64 encoded)', type: 'string', example: 'base64_encoded_receipt_data'),
                ]
            )
        ),
        tags: ['Subscriptions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Xác thực thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Receipt verified successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(
                                    property: 'subscription',
                                    properties: [
                                        new OA\Property(property: 'id', description: 'ID của subscription', type: 'integer', example: 1),
                                        new OA\Property(property: 'plan', description: 'Gói subscription', type: 'string', enum: ['basic', 'plus', 'all'], example: 'premium'),
                                        new OA\Property(property: 'status', description: 'Trạng thái subscription', type: 'string', enum: ['trial', 'active', 'expired', 'cancelled', 'grace_period', 'refunded'], example: 'active'),
                                        new OA\Property(property: 'provider', description: 'Nhà cung cấp', type: 'string', enum: ['google_iap', 'apple_iap'], example: 'apple_iap'),
                                        new OA\Property(property: 'expires_at', description: 'Thời gian hết hạn', type: 'string', format: 'date-time', example: '2026-02-30T09:00:00.000000Z', nullable: true),
                                        new OA\Property(property: 'trial_ends_at', description: 'Thời gian kết thúc trial', type: 'string', format: 'date-time', example: '2026-01-15T09:00:00.000000Z', nullable: true),
                                        new OA\Property(property: 'auto_renew', description: 'Tự động gia hạn', type: 'boolean', example: true),
                                        new OA\Property(property: 'amount', description: 'Giá tiền', type: 'number', format: 'float', example: 99.99),
                                        new OA\Property(property: 'currency', description: 'Đơn vị tiền tệ', type: 'string', example: 'USD'),
                                        new OA\Property(property: 'billing_cycle', description: 'Chu kỳ thanh toán', type: 'string', enum: ['monthly'], example: 'monthly'),
                                    ],
                                    type: 'object'
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Receipt không hợp lệ',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Invalid receipt: Receipt validation failed'),
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
                            example: ['receipt' => ['The receipt field is required.']],
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
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipt' => 'required|string',
        ]);

        $user = auth()->user();

        try {
            $subscription = $this->appleService->verifyReceipt(
                $validated['receipt'],
                $user
            );

            return ResponseAPI::success([
                'subscription' => [
                    'id' => $subscription->id,
                    'plan' => $subscription->plan->value,
                    'status' => $subscription->status->value,
                    'provider' => $subscription->provider->value,
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                    'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                    'auto_renew' => $subscription->auto_renew,
                    'amount' => $subscription->amount,
                    'currency' => $subscription->currency,
                    'billing_cycle' => $subscription->billing_cycle->value,
                ],
            ], __('messages.receipt_verified_successfully'));
        } catch (InvalidReceiptException $e) {
            return ResponseAPI::error(__('messages.invalid_receipt', ['detail' => $e->getMessage()]), 400);
        }
    }
}
