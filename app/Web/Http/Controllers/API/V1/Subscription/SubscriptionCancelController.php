<?php

namespace App\Web\Http\Controllers\API\V1\Subscription;

use App\Share\Enums\SubscriptionProvider;
use App\Share\Models\User;
use App\Share\Services\Program\ProgramSelectionService;
use App\Share\Services\Subscription\SubscriptionManager;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class SubscriptionCancelController extends BaseAPIController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly ProgramSelectionService $programSelectionService,
    ) {}

    /**
     * Hủy gia hạn tự động subscription (CANCEL RENEWAL)
     */
    #[OA\Post(
        path: '/subscriptions/cancel',
        description: 'Hủy auto-renew subscription đang active. Google Play: gọi provider cancel API (DB cập nhật qua webhook). Apple: không hỗ trợ outbound cancel — trả message hướng dẫn user hủy qua App Store.',
        summary: 'Hủy gia hạn subscription',
        security: [['bearerAuth' => []]],
        tags: ['Subscriptions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Hủy gia hạn thành công (hoặc Apple: hướng dẫn hủy thủ công)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 403, description: 'Không có subscription hợp lệ'),
            new OA\Response(response: 422, description: 'Không thể hủy gia hạn'),
            new OA\Response(response: 500, description: 'Lỗi provider'),
        ]
    )]
    public function store(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $subscription = $user->validSubscription()->with('googleSubscription')->first();

        if ($subscription === null) {
            return ResponseAPI::error(__('messages.no_active_subscription'), 403);
        }

        if (! $this->programSelectionService->canCancelRenewal($subscription)) {
            throw ValidationException::withMessages([
                'subscription' => [__('messages.subscription_cannot_cancel_renewal')],
            ]);
        }

        if ($subscription->provider->is(SubscriptionProvider::AppleIap)) {
            return ResponseAPI::success(
                null,
                __('messages.subscription_cancel_apple_manual')
            );
        }

        $this->subscriptionManager->cancelProvider($subscription);

        return ResponseAPI::success(null, __('messages.subscription_cancel_renewal_success'));
    }
}
