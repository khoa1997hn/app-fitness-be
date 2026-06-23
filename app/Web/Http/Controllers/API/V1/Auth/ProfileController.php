<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Services\Subscription\SubscriptionManager;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Concerns\MapsProgramForApi;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends BaseAPIController
{
    use MapsProgramForApi;

    public function __construct(private readonly SubscriptionManager $subscriptionManager) {}

    /**
     * Lấy thông tin user đang đăng nhập
     */
    #[OA\Get(
        path: '/auth/profile',
        description: 'Trả về thông tin chi tiết của user hiện tại đang đăng nhập (dựa trên JWT token)',
        summary: 'Lấy thông tin profile của user đang đăng nhập',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy thông tin profile thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', description: 'ID của user', type: 'integer', example: 1),
                                new OA\Property(property: 'email', description: 'Email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'first_name', description: 'Tên', type: 'string', example: 'Nguyễn'),
                                new OA\Property(property: 'last_name', description: 'Họ', type: 'string', example: 'Văn A'),
                                new OA\Property(property: 'phone', description: 'Số điện thoại', type: 'string', example: '0123456789', nullable: true),
                                new OA\Property(property: 'dob', description: 'Ngày sinh', type: 'string', format: 'date', example: '1990-01-01', nullable: true),
                                new OA\Property(property: 'plan', description: 'Gói subscription hiện tại', type: 'string', enum: ['basic', 'plus', 'all'], example: 'all', nullable: true),
                                new OA\Property(property: 'subscription_status', description: 'Trạng thái subscription', type: 'string', enum: ['trial', 'active', 'expired', 'cancelled', 'grace_period', 'refunded'], example: 'active', nullable: true),
                                new OA\Property(property: 'created_at', description: 'Thời gian tạo', type: 'string', format: 'date-time', example: '2026-01-30T09:00:00.000000Z'),
                                new OA\Property(property: 'updated_at', description: 'Thời gian cập nhật', type: 'string', format: 'date-time', example: '2026-01-30T09:00:00.000000Z'),
                                new OA\Property(
                                    property: 'favorited_programs',
                                    description: 'Danh sách program user đã yêu thích (mới nhất trước)',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'id', description: 'ID program', type: 'integer', example: 1),
                                            new OA\Property(property: 'name', description: 'Tên program (theo locale)', type: 'string', example: 'Pilates'),
                                            new OA\Property(
                                                property: 'cover',
                                                description: 'Ảnh cover (theo locale)',
                                                properties: [
                                                    new OA\Property(property: 'path', type: 'string', example: 'programs/cover/sample.jpg'),
                                                    new OA\Property(property: 'name', type: 'string', example: 'sample.jpg'),
                                                    new OA\Property(property: 'extension', type: 'string', example: 'jpg', nullable: true),
                                                    new OA\Property(property: 'size', type: 'integer', example: 102400, nullable: true),
                                                    new OA\Property(property: 'url', type: 'string', example: 'http://localhost/storage/programs/cover/sample.jpg'),
                                                ],
                                                type: 'object',
                                                nullable: true
                                            ),
                                            new OA\Property(property: 'rating', description: 'Đánh giá (admin nhập)', type: 'number', format: 'float', example: 4.9, nullable: true),
                                            new OA\Property(property: 'duration_minutes', description: 'Tổng thời lượng (phút)', type: 'integer', example: 30),
                                            new OA\Property(property: 'course_count', description: 'Số lượng bài học', type: 'integer', example: 12),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        ),
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
        ]
    )]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $favoritedPrograms = $user->favoritePrograms()
            ->withTranslation()
            ->with($this->programRelations())
            ->orderByPivot('created_at', 'desc')
            ->get()
            ->map(function (Program $program) {
                $mapped = $this->mapProgram($program);

                return [
                    'id' => $mapped['id'],
                    'name' => $mapped['name'],
                    'cover' => $mapped['cover'],
                    'rating' => $mapped['rating'],
                    'duration_minutes' => $mapped['duration_minutes'],
                    'course_count' => $mapped['course_count'],
                ];
            })
            ->all();

        return ResponseAPI::success([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'dob' => $user->dob,
            'plan' => $user->plan?->value,
            'subscription_status' => $user->subscription_status?->value,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'favorited_programs' => $favoritedPrograms,
        ]);
    }

    /**
     * Tự xóa tài khoản (soft delete)
     */
    #[OA\Delete(
        path: '/auth/me',
        description: 'Soft-delete tài khoản của user hiện tại. Nếu có subscription Google Play đang active: hủy phía Google trước (dừng auto-renew, user vẫn dùng đến hết kỳ) — nếu Google API fail thì trả 500, không xóa account. Apple subscription: bỏ qua (user hủy thủ công qua App Store). Sau khi provider cancel xong: JWT bị invalidate rồi soft-delete user. DB subscription được cập nhật qua webhook từ Google.',
        summary: 'Tự xóa tài khoản',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tài khoản đã được xóa thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Tài khoản đã được xóa thành công'),
                        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function destroy(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $subscription = $user->validSubscription()->with('googleSubscription')->first();

        if ($subscription) {
            // Throws on Google failure → 500, JWT still valid, user not deleted
            $this->subscriptionManager->cancelProvider($subscription);
        }

        JWTAuth::invalidate(JWTAuth::getToken());

        $user->delete();

        return ResponseAPI::success(null, 'Tài khoản đã được xóa thành công');
    }
}
