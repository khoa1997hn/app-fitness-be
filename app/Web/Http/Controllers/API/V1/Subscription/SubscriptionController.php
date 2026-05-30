<?php

namespace App\Web\Http\Controllers\API\V1\Subscription;

use App\Share\Enums\SubscriptionProvider;
use App\Share\Models\User;
use App\Share\Services\Program\ProgramSelectionService;
use App\Share\Services\Subscription\SubscriptionManager;
use App\Share\Services\Subscription\SubscriptionService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class SubscriptionController extends BaseAPIController
{
    public function __construct(
        private readonly SubscriptionManager $subscriptionManager,
        private readonly SubscriptionService $subscriptionService,
        private readonly ProgramSelectionService $programSelectionService,
    ) {}

    /**
     * Thông tin subscription hiện tại + programs đã chọn
     */
    #[OA\Get(
        path: '/subscriptions/me',
        description: 'Lấy thông tin subscription hiện tại (mọi trạng thái) kèm danh sách programs đã chọn. Plan All Access: selected_programs = null. Không có subscription: data = null.',
        summary: 'Thông tin subscription hiện tại',
        security: [['bearerAuth' => []]],
        tags: ['Subscriptions'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy thông tin thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 10),
                                new OA\Property(property: 'plan', type: 'string', example: 'basic', description: 'basic | plus | all'),
                                new OA\Property(property: 'status', type: 'string', example: 'active', description: 'trial | active | grace_period | cancelled | expired'),
                                new OA\Property(property: 'provider', type: 'string', example: 'google_iap'),
                                new OA\Property(property: 'amount', type: 'number', format: 'float', example: 1999000),
                                new OA\Property(property: 'currency', type: 'string', example: 'VND'),
                                new OA\Property(property: 'auto_renew', type: 'boolean', example: true),
                                new OA\Property(property: 'started_at', type: 'string', format: 'date-time', example: '2026-01-01T00:00:00+07:00'),
                                new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'renews_at', type: 'string', format: 'date-time', nullable: true, description: 'Ngày gia hạn tiếp theo. Null khi đã hủy gia hạn hoặc không hợp lệ.'),
                                new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'show_plan_ends_notice', type: 'boolean', example: false, description: 'true khi auto_renew=false và còn trong kỳ hợp lệ'),
                                new OA\Property(property: 'can_cancel_renewal', type: 'boolean', example: true, description: 'Nút CANCEL RENEWAL'),
                                new OA\Property(property: 'can_renew', type: 'boolean', example: false, description: 'Nút RENEW SUBSCRIPTION'),
                                new OA\Property(property: 'requires_selection', type: 'boolean', example: true),
                                new OA\Property(property: 'max_programs', type: 'integer', example: 1, nullable: true),
                                new OA\Property(
                                    property: 'allowed_lesson_types',
                                    type: 'array',
                                    items: new OA\Items(type: 'string', example: 'level')
                                ),
                                new OA\Property(
                                    property: 'selected_programs',
                                    type: 'array',
                                    nullable: true,
                                    description: 'null cho plan All Access; danh sách program đã chọn cho Basic/Plus',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 7),
                                            new OA\Property(property: 'name', type: 'string', example: 'Barre'),
                                            new OA\Property(property: 'selected_at', type: 'string', format: 'date-time', example: '2026-05-29T10:00:00+07:00'),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object',
                            nullable: true
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
        ]
    )]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $this->subscriptionService->getSubscriptionData($user);

        if ($data !== null) {
            $data['selected_programs'] = $this->programSelectionService->getSelectedPrograms($user->subscription);
        }

        return ResponseAPI::success($data);
    }

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
    public function cancel(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $subscription = $user->validSubscription()->with('googleSubscription')->first();

        if ($subscription === null) {
            return ResponseAPI::error(__('messages.no_active_subscription'), 403);
        }

        if (! $this->subscriptionService->canCancelRenewal($subscription)) {
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
