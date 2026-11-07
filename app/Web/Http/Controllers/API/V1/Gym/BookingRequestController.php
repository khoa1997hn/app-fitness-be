<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Enums\PrivateDiscipline;
use App\Share\Models\PrivateRequest;
use App\Share\Models\TherapyRequest;
use App\Share\Models\User;
use App\Share\Services\Gym\ExtraPeopleService;
use App\Share\Services\Gym\PrivateRequestService;
use App\Share\Services\Gym\TherapyRequestService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsBookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class BookingRequestController extends BaseAPIController
{
    use MapsBookingRequest;

    public function __construct(
        private readonly PrivateRequestService $privateRequestService,
        private readonly TherapyRequestService $therapyRequestService,
        private readonly ExtraPeopleService $extraPeopleService,
    ) {}

    #[OA\Get(
        path: '/users/me/extra-people',
        description: 'Số dư suất người tham gia thêm của tài khoản, dùng chung cho các gói `private_pack`. `available` là số còn dùng được sau khi trừ các yêu cầu đang mở.',
        summary: 'Số dư suất người thêm',
        security: [['bearerAuth' => []]],
        tags: ['Gym Requests'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'purchased_total', type: 'integer', example: 10),
                    new OA\Property(property: 'used', type: 'integer', example: 2),
                    new OA\Property(property: 'reserved', type: 'integer', example: 1),
                    new OA\Property(property: 'balance', type: 'integer', example: 8),
                    new OA\Property(property: 'available', type: 'integer', example: 7),
                    new OA\Property(property: 'price_per_slot', type: 'integer', example: 200000),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function extraPeople(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return ResponseAPI::success([
            ...$this->extraPeopleService->summary($user),
            'price_per_slot' => (int) config('gym.extra_people_price'),
        ]);
    }

    #[OA\Get(
        path: '/users/me/private-requests',
        description: 'Yêu cầu Private của tôi, không gồm yêu cầu đã hủy.',
        summary: 'Yêu cầu Private của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Requests'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(allOf: [
                    new OA\Schema(ref: '#/components/schemas/GymPrivateRequest'),
                    new OA\Schema(properties: [new OA\Property(property: 'can_cancel', type: 'boolean', example: true)]),
                ])),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function privateRequests(): JsonResponse
    {
        $this->privateRequestService->cancelExpiredPending();

        $requests = PrivateRequest::query()
            ->with(['userPackage', 'trainer'])
            ->where('user_id', auth()->id())
            ->whereIn('status', $this->memberVisibleStatuses())
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($requests->map(fn (PrivateRequest $request) => [
            ...$this->mapPrivateRequest($request),
            'can_cancel' => $this->privateRequestService->canMemberCancel($request),
        ])->all());
    }

    #[OA\Post(
        path: '/private-requests',
        description: 'Gửi yêu cầu lịch Private. Trừ tạm 1 buổi ngay, bị từ chối hoặc hủy hợp lệ thì hoàn lại. `extra_people_count` chỉ áp dụng với `private_pack`, `private_group` phải bằng 0.',
        summary: 'Gửi yêu cầu Private',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['user_package_id', 'discipline', 'requested_at', 'people_count'],
            properties: [
                new OA\Property(property: 'user_package_id', type: 'integer', example: 7),
                new OA\Property(property: 'discipline', type: 'string', enum: ['Yoga', 'Pilates', 'Barre'], example: 'Yoga'),
                new OA\Property(property: 'requested_at', type: 'string', example: '2026-11-20T15:00'),
                new OA\Property(property: 'people_count', type: 'integer', example: 2),
                new OA\Property(property: 'extra_people_count', type: 'integer', example: 0),
                new OA\Property(property: 'note', type: 'string', nullable: true),
            ]
        )),
        tags: ['Gym Requests'],
        responses: [
            new OA\Response(response: 201, description: 'Đã gửi', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymPrivateRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 404, description: 'Không tìm thấy gói'),
            new OA\Response(response: 422, description: 'Dữ liệu hoặc điều kiện gói không hợp lệ'),
        ]
    )]
    public function storePrivate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_package_id' => 'required|integer',
            'discipline' => ['required', Rule::in(PrivateDiscipline::getValues())],
            'requested_at' => 'required|date',
            'people_count' => 'required|integer|min:1',
            'extra_people_count' => 'sometimes|integer|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $created = $this->privateRequestService->create($user, $validated);

        return ResponseAPI::success($this->mapPrivateRequest($created->load(['userPackage', 'trainer'])), code: 201);
    }

    #[OA\Patch(
        path: '/private-requests/{privateRequest}/cancel',
        description: 'Hủy yêu cầu. Chờ duyệt thì hủy lúc nào cũng được. Đã duyệt thì bị chặn trong vòng 8 tiếng trước giờ tập.',
        summary: 'Hủy yêu cầu Private',
        security: [['bearerAuth' => []]],
        tags: ['Gym Requests'],
        parameters: [
            new OA\Parameter(name: 'privateRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã hủy', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'string', example: null, nullable: true),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Không thể hủy'),
        ]
    )]
    public function cancelPrivate(PrivateRequest $privateRequest): JsonResponse
    {
        abort_unless($privateRequest->user_id === auth()->id(), 404);

        $this->privateRequestService->cancelByMember($privateRequest);

        return ResponseAPI::success();
    }

    #[OA\Get(
        path: '/users/me/therapy-requests',
        description: 'Yêu cầu trị liệu của tôi, không gồm yêu cầu đã hủy.',
        summary: 'Yêu cầu trị liệu của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Requests'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(allOf: [
                    new OA\Schema(ref: '#/components/schemas/GymTherapyRequest'),
                    new OA\Schema(properties: [new OA\Property(property: 'can_cancel', type: 'boolean', example: true)]),
                ])),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function therapyRequests(): JsonResponse
    {
        $requests = TherapyRequest::query()
            ->with('userTherapy')
            ->where('user_id', auth()->id())
            ->whereIn('status', $this->memberVisibleStatuses())
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($requests->map(fn (TherapyRequest $request) => [
            ...$this->mapTherapyRequest($request),
            'can_cancel' => $this->therapyRequestService->canMemberCancel($request),
        ])->all());
    }

    #[OA\Post(
        path: '/therapy-requests',
        description: 'Gửi yêu cầu lịch trị liệu. Buổi chỉ bị trừ khi admin duyệt.',
        summary: 'Gửi yêu cầu trị liệu',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['user_therapy_id', 'preferred_at'],
            properties: [
                new OA\Property(property: 'user_therapy_id', type: 'integer', example: 2),
                new OA\Property(property: 'preferred_at', type: 'string', example: '2026-11-20T15:00'),
                new OA\Property(property: 'note', type: 'string', nullable: true),
            ]
        )),
        tags: ['Gym Requests'],
        responses: [
            new OA\Response(response: 201, description: 'Đã gửi', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymTherapyRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 404, description: 'Không tìm thấy gói trị liệu'),
            new OA\Response(response: 422, description: 'Hết buổi, hết hạn hoặc thời gian đã qua'),
        ]
    )]
    public function storeTherapy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_therapy_id' => 'required|integer',
            'preferred_at' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $created = $this->therapyRequestService->create($user, $validated);

        return ResponseAPI::success($this->mapTherapyRequest($created->load('userTherapy')), code: 201);
    }

    #[OA\Patch(
        path: '/therapy-requests/{therapyRequest}/cancel',
        description: 'Hủy yêu cầu. Lịch đã duyệt bị chặn trong vòng 8 tiếng; hủy hợp lệ được hoàn buổi.',
        summary: 'Hủy yêu cầu trị liệu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Requests'],
        parameters: [
            new OA\Parameter(name: 'therapyRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 6)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã hủy', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'string', example: null, nullable: true),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Không thể hủy'),
        ]
    )]
    public function cancelTherapy(TherapyRequest $therapyRequest): JsonResponse
    {
        abort_unless($therapyRequest->user_id === auth()->id(), 404);

        $this->therapyRequestService->cancelByMember($therapyRequest);

        return ResponseAPI::success();
    }
}
