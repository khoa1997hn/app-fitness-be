<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\PrivateDiscipline;
use App\Share\Enums\Role;
use App\Share\Models\PrivateRequest;
use App\Share\Models\TherapyRequest;
use App\Share\Models\User;
use App\Share\Models\UserTherapy;
use App\Share\Services\Gym\PrivateRequestService;
use App\Share\Services\Gym\TherapyRequestService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsBookingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymAdminPrivateRequest',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymPrivateRequest'),
        new OA\Schema(properties: [
            new OA\Property(property: 'member', properties: [
                new OA\Property(property: 'id', type: 'integer', example: 4),
                new OA\Property(property: 'full_name', type: 'string', example: 'Phạm Thị Member'),
            ], type: 'object'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'GymAdminTherapyRequest',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymTherapyRequest'),
        new OA\Schema(properties: [
            new OA\Property(property: 'member', properties: [
                new OA\Property(property: 'id', type: 'integer', example: 4),
                new OA\Property(property: 'full_name', type: 'string', example: 'Phạm Thị Member'),
            ], type: 'object'),
        ]),
    ]
)]
class BookingRequestController extends BaseAPIController
{
    use MapsBookingRequest;

    public function __construct(
        private readonly PrivateRequestService $privateRequestService,
        private readonly TherapyRequestService $therapyRequestService,
    ) {}

    #[OA\Get(
        path: '/admin/private-requests',
        description: 'Yêu cầu Private, mới nhất trước. Quyền `bookings.manage`.',
        summary: 'Admin: danh sách yêu cầu Private',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Requests'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'])),
            new OA\Parameter(name: 'q', description: 'Tìm theo tên học viên, gói', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAdminPrivateRequest')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function privateIndex(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request);

        $requests = PrivateRequest::query()
            ->with(['user', 'userPackage', 'trainer'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->whereHas('userPackage', fn ($package) => $package->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($user) => $user->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])));
            })
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($requests->map(fn (PrivateRequest $item) => $this->mapAdminPrivate($item))->all());
    }

    #[OA\Get(
        path: '/admin/private-requests/{privateRequest}',
        description: 'Chi tiết yêu cầu Private. Quyền `bookings.manage`.',
        summary: 'Admin: chi tiết yêu cầu Private',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Requests'],
        parameters: [new OA\Parameter(name: 'privateRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminPrivateRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function privateShow(PrivateRequest $privateRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminPrivate($privateRequest));
    }

    #[OA\Patch(
        path: '/admin/private-requests/{privateRequest}',
        description: 'Sửa form khi còn chờ duyệt, hoặc đã duyệt mà buổi chưa bắt đầu. Sửa lịch đã duyệt thì gửi thông báo cho học viên. Quyền `bookings.private.edit_form`.',
        summary: 'Admin: sửa yêu cầu Private',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['discipline', 'requested_at', 'people_count'],
            properties: [
                new OA\Property(property: 'discipline', type: 'string', enum: ['Yoga', 'Pilates', 'Barre'], example: 'Yoga'),
                new OA\Property(property: 'requested_at', type: 'string', example: '2026-11-20T15:00'),
                new OA\Property(property: 'people_count', type: 'integer', example: 2),
                new OA\Property(property: 'extra_people_count', type: 'integer', example: 0),
                new OA\Property(property: 'trainer_id', description: 'User có role trainer', type: 'integer', nullable: true),
                new OA\Property(property: 'note', type: 'string', nullable: true),
            ]
        )),
        tags: ['Gym Admin Requests'],
        parameters: [new OA\Parameter(name: 'privateRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5))],
        responses: [
            new OA\Response(response: 200, description: 'Đã cập nhật', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminPrivateRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ, trạng thái không cho sửa hoặc buổi đã bắt đầu'),
        ]
    )]
    public function privateUpdate(Request $request, PrivateRequest $privateRequest): JsonResponse
    {
        $validated = $request->validate([
            'discipline' => ['required', Rule::in(PrivateDiscipline::getValues())],
            'requested_at' => 'required|date',
            'people_count' => 'required|integer|min:1',
            'extra_people_count' => 'sometimes|integer|min:0',
            'trainer_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', Role::Trainer)->whereNull('deleted_at')],
            'note' => 'nullable|string|max:1000',
        ]);

        return ResponseAPI::success($this->mapAdminPrivate($this->privateRequestService->update($privateRequest, $validated)->refresh()));
    }

    #[OA\Patch(
        path: '/admin/private-requests/{privateRequest}/{action}',
        description: '`approve` duyệt, `reject` từ chối (quyền `bookings.private.approve`). `complete` xác nhận buổi đã diễn ra, trừ 1 buổi và suất người thêm, kể cả khi học viên vắng (quyền `bookings.private.approve`). `cancel` admin hủy lịch đã duyệt, không trừ buổi (quyền `bookings.cancel_class`).',
        summary: 'Admin: xử lý yêu cầu Private',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Requests'],
        parameters: [
            new OA\Parameter(name: 'privateRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 5)),
            new OA\Parameter(name: 'action', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['approve', 'reject', 'complete', 'cancel'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminPrivateRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Trạng thái hoặc thời gian không phù hợp'),
        ]
    )]
    public function privateApprove(PrivateRequest $privateRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminPrivate($this->privateRequestService->approve($privateRequest, $this->staff())));
    }

    public function privateReject(PrivateRequest $privateRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminPrivate($this->privateRequestService->reject($privateRequest, $this->staff())));
    }

    public function privateComplete(PrivateRequest $privateRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminPrivate($this->privateRequestService->complete($privateRequest, $this->staff())));
    }

    public function privateCancel(PrivateRequest $privateRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminPrivate($this->privateRequestService->cancelByStaff($privateRequest, $this->staff())));
    }

    #[OA\Get(
        path: '/admin/therapy-requests',
        description: 'Yêu cầu trị liệu, mới nhất trước. Quyền `bookings.manage`.',
        summary: 'Admin: danh sách yêu cầu trị liệu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Requests'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'rejected', 'cancelled'])),
            new OA\Parameter(name: 'q', description: 'Tìm theo tên học viên, dịch vụ', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAdminTherapyRequest')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function therapyIndex(Request $request): JsonResponse
    {
        $validated = $this->validateFilters($request);

        $requests = TherapyRequest::query()
            ->with(['user', 'userTherapy'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->whereHas('userTherapy', fn ($therapy) => $therapy->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($user) => $user->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])));
            })
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($requests->map(fn (TherapyRequest $item) => $this->mapAdminTherapy($item))->all());
    }

    #[OA\Get(
        path: '/admin/therapy-requests/{therapyRequest}',
        description: 'Chi tiết yêu cầu trị liệu. Quyền `bookings.manage`.',
        summary: 'Admin: chi tiết yêu cầu trị liệu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Requests'],
        parameters: [new OA\Parameter(name: 'therapyRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 6))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminTherapyRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function therapyShow(TherapyRequest $therapyRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminTherapy($therapyRequest));
    }

    #[OA\Patch(
        path: '/admin/therapy-requests/{therapyRequest}',
        description: 'Sửa thời gian, ghi chú khi còn chờ duyệt. Quyền `bookings.private.edit_form`.',
        summary: 'Admin: sửa yêu cầu trị liệu',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['preferred_at'],
            properties: [
                new OA\Property(property: 'preferred_at', type: 'string', example: '2026-11-20T15:00'),
                new OA\Property(property: 'note', type: 'string', nullable: true),
            ]
        )),
        tags: ['Gym Admin Requests'],
        parameters: [new OA\Parameter(name: 'therapyRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 6))],
        responses: [
            new OA\Response(response: 200, description: 'Đã cập nhật', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminTherapyRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc yêu cầu không còn chờ duyệt'),
        ]
    )]
    public function therapyUpdate(Request $request, TherapyRequest $therapyRequest): JsonResponse
    {
        $validated = $request->validate([
            'preferred_at' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        return ResponseAPI::success($this->mapAdminTherapy($this->therapyRequestService->update($therapyRequest, $validated)->refresh()));
    }

    #[OA\Patch(
        path: '/admin/therapy-requests/{therapyRequest}/{action}',
        description: '`approve` duyệt và trừ 1 buổi, `reject` từ chối (quyền `bookings.private.approve`). `cancel` admin hủy lịch đã duyệt, hoàn buổi (quyền `bookings.cancel_class`).',
        summary: 'Admin: xử lý yêu cầu trị liệu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Requests'],
        parameters: [
            new OA\Parameter(name: 'therapyRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 6)),
            new OA\Parameter(name: 'action', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['approve', 'reject', 'cancel'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminTherapyRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Trạng thái, thời gian hoặc số buổi không phù hợp'),
        ]
    )]
    public function therapyApprove(TherapyRequest $therapyRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminTherapy($this->therapyRequestService->approve($therapyRequest, $this->staff())));
    }

    public function therapyReject(TherapyRequest $therapyRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminTherapy($this->therapyRequestService->reject($therapyRequest, $this->staff())));
    }

    public function therapyCancel(TherapyRequest $therapyRequest): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminTherapy($this->therapyRequestService->cancelByStaff($therapyRequest, $this->staff())));
    }

    #[OA\Get(
        path: '/admin/bookings/therapy-members',
        description: 'Học viên có gói trị liệu. Quyền `bookings.manage`.',
        summary: 'Admin: học viên gói trị liệu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Requests'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Tìm theo tên học viên, dịch vụ', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 2),
                    new OA\Property(property: 'member', properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 4),
                        new OA\Property(property: 'full_name', type: 'string', example: 'Phạm Thị Member'),
                    ], type: 'object'),
                    new OA\Property(property: 'therapy_name', type: 'string', example: 'Deep Stretch — Combo 5 buổi'),
                    new OA\Property(property: 'sessions_total', type: 'integer', example: 5),
                    new OA\Property(property: 'sessions_remaining', type: 'integer', example: 4),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-01-31'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'expired'], example: 'active'),
                ], type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function therapyMembers(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => 'nullable|string|max:255']);

        $therapies = UserTherapy::query()
            ->with('user')
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])));
            })
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($therapies->map(function (UserTherapy $therapy) {
            $status = 'active';
            if ($therapy->end_date->endOfDay()->isPast()) {
                $status = 'expired';
            } elseif ($therapy->sessions_remaining === 0) {
                $status = 'completed';
            }

            return [
                'id' => $therapy->id,
                'member' => [
                    'id' => $therapy->user->id,
                    'full_name' => trim($therapy->user->first_name.' '.$therapy->user->last_name),
                ],
                'therapy_name' => $therapy->name,
                'sessions_total' => $therapy->sessions_total,
                'sessions_remaining' => $therapy->sessions_remaining,
                'end_date' => $therapy->end_date->toDateString(),
                'status' => $status,
            ];
        })->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'status' => ['nullable', Rule::in(BookingRequestStatus::getValues())],
            'q' => 'nullable|string|max:255',
        ]);
    }

    private function staff(): User
    {
        /** @var User $staff */
        $staff = auth()->user();

        return $staff;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAdminPrivate(PrivateRequest $request): array
    {
        $request->loadMissing(['user', 'userPackage', 'trainer']);

        return [
            ...$this->mapPrivateRequest($request),
            'member' => [
                'id' => $request->user->id,
                'full_name' => trim($request->user->first_name.' '.$request->user->last_name),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAdminTherapy(TherapyRequest $request): array
    {
        $request->loadMissing(['user', 'userTherapy']);

        return [
            ...$this->mapTherapyRequest($request),
            'member' => [
                'id' => $request->user->id,
                'full_name' => trim($request->user->first_name.' '.$request->user->last_name),
            ],
        ];
    }
}
