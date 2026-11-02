<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\ScheduleCancelRequestStatus;
use App\Share\Models\ScheduleCancelRequest;
use App\Share\Models\User;
use App\Share\Services\Gym\ScheduleCancelRequestService;
use App\Share\Services\Gym\TeachingScheduleService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsTeachingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymCancelRequestReview',
    properties: [new OA\Property(property: 'note', type: 'string', nullable: true, example: 'Đã sắp HLV khác báo học viên')]
)]
class ScheduleCancelRequestController extends BaseAPIController
{
    use MapsTeachingSchedule;

    public function __construct(
        private readonly ScheduleCancelRequestService $cancelRequestService,
        private readonly TeachingScheduleService $teachingScheduleService,
    ) {}

    #[OA\Post(
        path: '/instructor/schedules/{type}/{id}/cancel-request',
        description: 'HLV xin hủy lịch của mình, được gửi cả trong vòng 8 tiếng trước giờ dạy, miễn là buổi chưa bắt đầu. Quyền `schedules.cancel_request.create`.',
        summary: 'HLV: gửi yêu cầu hủy lịch dạy',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['reason'], properties: [
            new OA\Property(property: 'reason', type: 'string', example: 'Bị ốm'),
        ])),
        tags: ['Gym Admin Teaching'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['class', 'private'])),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymScheduleCancelRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không phải lịch của HLV này'),
            new OA\Response(response: 422, description: 'Lịch không hủy được hoặc đã có yêu cầu chờ duyệt'),
        ]
    )]
    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        /** @var User $trainer */
        $trainer = auth()->user();
        $target = $this->teachingScheduleService->find($type, $id, $trainer);

        return ResponseAPI::success($this->mapRequest($this->cancelRequestService->create($target, $trainer, $validated['reason'])));
    }

    #[OA\Get(
        path: '/admin/schedule-cancel-requests',
        description: 'Yêu cầu hủy của HLV, mới nhất trước. Quyền `schedules.cancel_request.review`.',
        summary: 'Admin: danh sách yêu cầu hủy của HLV',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Teaching'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymScheduleCancelRequest')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(ScheduleCancelRequestStatus::getValues())],
        ]);

        $requests = $this->withRelations(ScheduleCancelRequest::query())
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->get();

        return ResponseAPI::success($requests->map(fn (ScheduleCancelRequest $item) => $this->mapScheduleCancelRequest($item, $this->teachingScheduleService))->all());
    }

    #[OA\Patch(
        path: '/admin/schedule-cancel-requests/{scheduleCancelRequest}/approve',
        description: 'Duyệt: hủy lớp hoặc buổi Private liên quan, học viên không bị trừ buổi. Quyền `schedules.cancel_request.review`.',
        summary: 'Admin: duyệt yêu cầu hủy của HLV',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/GymCancelRequestReview')),
        tags: ['Gym Admin Teaching'],
        parameters: [new OA\Parameter(name: 'scheduleCancelRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymScheduleCancelRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Yêu cầu đã xử lý, hoặc buổi đã diễn ra'),
        ]
    )]
    public function approve(Request $request, ScheduleCancelRequest $scheduleCancelRequest): JsonResponse
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);
        /** @var User $staff */
        $staff = auth()->user();

        return ResponseAPI::success($this->mapRequest($this->cancelRequestService->approve($scheduleCancelRequest, $staff, $validated['note'] ?? null)));
    }

    #[OA\Patch(
        path: '/admin/schedule-cancel-requests/{scheduleCancelRequest}/reject',
        description: 'Từ chối: lịch giữ nguyên. Quyền `schedules.cancel_request.review`.',
        summary: 'Admin: từ chối yêu cầu hủy của HLV',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(ref: '#/components/schemas/GymCancelRequestReview')),
        tags: ['Gym Admin Teaching'],
        parameters: [new OA\Parameter(name: 'scheduleCancelRequest', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymScheduleCancelRequest'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Yêu cầu đã xử lý'),
        ]
    )]
    public function reject(Request $request, ScheduleCancelRequest $scheduleCancelRequest): JsonResponse
    {
        $validated = $request->validate(['note' => 'nullable|string|max:1000']);
        /** @var User $staff */
        $staff = auth()->user();

        return ResponseAPI::success($this->mapRequest($this->cancelRequestService->reject($scheduleCancelRequest, $staff, $validated['note'] ?? null)));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRequest(ScheduleCancelRequest $request): array
    {
        return $this->mapScheduleCancelRequest(
            $this->withRelations(ScheduleCancelRequest::query())->findOrFail($request->id),
            $this->teachingScheduleService,
        );
    }

    private function withRelations($query)
    {
        return $query->with([
            'trainer',
            'classSchedule' => fn ($schedule) => $schedule
                ->with(['fitnessClass', 'trainer'])
                ->withCount('confirmedBookings')
                ->withExists('pendingCancelRequests'),
            'privateRequest' => fn ($private) => $private
                ->with(['userPackage', 'trainer'])
                ->withExists('pendingCancelRequests'),
        ]);
    }
}
