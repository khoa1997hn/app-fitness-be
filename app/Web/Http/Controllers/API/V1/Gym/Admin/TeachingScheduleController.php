<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\Role;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;
use App\Share\Services\Gym\TeachingScheduleService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsTeachingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymTeachingScheduleDetail',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymTeachingSchedule'),
        new OA\Schema(properties: [
            new OA\Property(property: 'notes', type: 'string', nullable: true),
            new OA\Property(property: 'students', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'booking_id', description: 'Null với buổi Private', type: 'integer', nullable: true, example: 21),
                new OA\Property(property: 'user_id', type: 'integer', example: 7),
                new OA\Property(property: 'name', type: 'string', example: 'Phạm Thị Member'),
                new OA\Property(property: 'package_name', type: 'string', example: '10 classes'),
                new OA\Property(property: 'remaining_credits', type: 'integer', nullable: true, example: 4),
                new OA\Property(property: 'status', type: 'string', example: 'confirmed'),
                new OA\Property(property: 'has_redlight', description: 'Đã trả tiền Redlight', type: 'boolean', example: false),
                new OA\Property(property: 'redlight_refund_status', description: '`pending` cần hoàn tiền thủ công, `refunded` đã hoàn', type: 'string', enum: ['pending', 'refunded'], nullable: true),
            ], type: 'object')),
        ]),
    ]
)]
class TeachingScheduleController extends BaseAPIController
{
    use MapsTeachingSchedule;

    public function __construct(private readonly TeachingScheduleService $teachingScheduleService) {}

    #[OA\Get(
        path: '/admin/schedules',
        description: 'Mọi lớp gói thường trên thời khóa biểu và buổi Private đã duyệt, mới nhất trước. HLV chỉ thấy lịch của mình. Quyền `schedules.teaching.view`.',
        summary: 'Danh sách lịch dạy',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Teaching'],
        parameters: [
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['confirmed', 'completed', 'cancelled'])),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['class', 'private'])),
            new OA\Parameter(name: 'q', description: 'Tìm theo tên lớp, bộ môn, HLV', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymTeachingSchedule')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|in:confirmed,completed,cancelled',
            'type' => 'nullable|in:class,private',
            'q' => 'nullable|string|max:255',
        ]);
        /** @var User $viewer */
        $viewer = auth()->user();
        $type = $validated['type'] ?? null;
        $date = $validated['date'] ?? null;
        $q = $validated['q'] ?? null;
        $ownOnly = $viewer->role->is(Role::Trainer);
        $trainerLike = fn ($trainer, string $like) => $trainer->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like]);

        $classes = $type === TeachingScheduleService::TypePrivate ? collect() : ClassSchedule::query()
            ->with(['fitnessClass', 'trainer'])
            ->withCount('confirmedBookings')
            ->withExists('pendingCancelRequests')
            ->when($ownOnly, fn ($query) => $query->where('trainer_id', $viewer->id))
            ->when($date, fn ($query) => $query->whereDate('start_time', $date))
            ->when($q, function ($query, string $q) use ($trainerLike) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->whereHas('fitnessClass', fn ($class) => $class->where('name', 'like', $like))
                    ->orWhereHas('trainer', fn ($trainer) => $trainerLike($trainer, $like)));
            })
            ->get()
            ->map(fn (ClassSchedule $schedule) => $this->mapTeachingClass($schedule, $this->teachingScheduleService));

        $privates = $type === TeachingScheduleService::TypeClass ? collect() : PrivateRequest::query()
            ->with(['userPackage', 'trainer'])
            ->withExists('pendingCancelRequests')
            ->whereNotNull('reviewed_by')
            ->whereIn('status', [BookingRequestStatus::Confirmed, BookingRequestStatus::Completed, BookingRequestStatus::Cancelled])
            ->when($ownOnly, fn ($query) => $query->where('trainer_id', $viewer->id))
            ->when($date, fn ($query) => $query->whereDate('requested_at', $date))
            ->when($q, function ($query, string $q) use ($trainerLike) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->where('discipline', 'like', $like)
                    ->orWhereHas('trainer', fn ($trainer) => $trainerLike($trainer, $like)));
            })
            ->get()
            ->map(fn (PrivateRequest $private) => $this->mapTeachingPrivate($private, $this->teachingScheduleService));

        $items = $classes->concat($privates)
            ->when($validated['status'] ?? null, fn ($items, string $status) => $items->where('status', $status))
            ->sortByDesc('start_time')
            ->values();

        return ResponseAPI::success($items->all());
    }

    #[OA\Get(
        path: '/admin/schedules/{type}/{id}',
        description: 'Chi tiết lịch dạy kèm học viên. HLV chỉ xem được lịch của mình. Quyền `schedules.teaching.view`.',
        summary: 'Chi tiết lịch dạy',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Teaching'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['class', 'private'])),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymTeachingScheduleDetail'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(string $type, int $id): JsonResponse
    {
        /** @var User $viewer */
        $viewer = auth()->user();

        return ResponseAPI::success($this->mapDetail($this->teachingScheduleService->find($type, $id, $viewer)));
    }

    #[OA\Patch(
        path: '/admin/schedules/{type}/{id}/cancel',
        description: 'Hủy lớp hoặc buổi Private, học viên không bị trừ buổi. Quyền `schedules.teaching.cancel`.',
        summary: 'Admin: hủy lịch dạy',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Teaching'],
        parameters: [
            new OA\Parameter(name: 'type', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['class', 'private'])),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymTeachingScheduleDetail'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Lịch đã hủy hoặc đã diễn ra'),
        ]
    )]
    public function cancel(string $type, int $id): JsonResponse
    {
        /** @var User $staff */
        $staff = auth()->user();
        $target = $this->teachingScheduleService->find($type, $id, $staff);
        $this->teachingScheduleService->cancel($target, $staff);

        return ResponseAPI::success($this->mapDetail($target->refresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDetail(ClassSchedule|PrivateRequest $target): array
    {
        if ($target instanceof ClassSchedule) {
            $target->load(['fitnessClass', 'trainer', 'bookings.user', 'bookings.userPackage'])
                ->loadCount('confirmedBookings')
                ->loadExists('pendingCancelRequests');

            return [
                ...$this->mapTeachingClass($target, $this->teachingScheduleService),
                'notes' => $target->notes,
                'students' => $target->bookings
                    ->sortBy(fn (Booking $booking) => $booking->status->is(BookingStatus::Confirmed) ? 0 : 1)
                    ->values()
                    ->map(fn (Booking $booking) => [
                        'booking_id' => $booking->id,
                        'user_id' => $booking->user_id,
                        'name' => trim($booking->user->first_name.' '.$booking->user->last_name),
                        'package_name' => $booking->userPackage->name,
                        'remaining_credits' => $booking->userPackage->remaining_credits,
                        'status' => $booking->status,
                        'has_redlight' => $booking->redlight_paid_at !== null,
                        'redlight_refund_status' => $booking->redlightRefundStatus(),
                    ])->all(),
            ];
        }

        $target->load(['userPackage', 'trainer', 'user'])->loadExists('pendingCancelRequests');

        return [
            ...$this->mapTeachingPrivate($target, $this->teachingScheduleService),
            'notes' => $target->note,
            'students' => [[
                'booking_id' => null,
                'user_id' => $target->user_id,
                'name' => trim($target->user->first_name.' '.$target->user->last_name),
                'package_name' => $target->userPackage->name,
                'remaining_credits' => $target->userPackage->remaining_credits,
                'status' => $target->status,
                'has_redlight' => false,
                'redlight_refund_status' => null,
            ]],
        ];
    }
}
