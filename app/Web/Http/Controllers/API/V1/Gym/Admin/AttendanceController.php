<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\AttendanceStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\Role;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\User;
use App\Share\Services\Gym\AttendanceService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymAttendanceSession',
    required: ['id', 'class_name', 'trainer_id', 'trainer_name', 'room', 'start_time', 'end_time', 'editable', 'members'],
    properties: [
        new OA\Property(property: 'id', description: 'ID lớp', type: 'integer', example: 15),
        new OA\Property(property: 'class_name', type: 'string', example: 'Yoga Flow'),
        new OA\Property(property: 'trainer_id', type: 'integer', example: 3),
        new OA\Property(property: 'trainer_name', type: 'string', example: 'Nguyễn Thu Hà'),
        new OA\Property(property: 'room', type: 'string', example: 'P101'),
        new OA\Property(property: 'start_time', type: 'string', example: '2026-11-05T07:00:00'),
        new OA\Property(property: 'end_time', type: 'string', example: '2026-11-05T08:00:00'),
        new OA\Property(property: 'editable', description: 'Buổi diễn ra hôm nay', type: 'boolean', example: true),
        new OA\Property(property: 'members', type: 'array', items: new OA\Items(properties: [
            new OA\Property(property: 'booking_id', type: 'integer', example: 21),
            new OA\Property(property: 'user_id', type: 'integer', example: 7),
            new OA\Property(property: 'member_name', type: 'string', example: 'Phạm Thị Member'),
            new OA\Property(property: 'package_name', type: 'string', example: '10 classes'),
            new OA\Property(property: 'absent_recent', description: 'Số buổi vắng trong 5 lần điểm danh gần nhất', type: 'integer', example: 1),
            new OA\Property(property: 'attendance_status', type: 'string', enum: ['present', 'absent'], nullable: true),
            new OA\Property(property: 'has_redlight', description: 'Cần chuẩn bị thảm Redlight', type: 'boolean', example: false),
        ], type: 'object')),
    ]
)]
class AttendanceController extends BaseAPIController
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    #[OA\Get(
        path: '/admin/attendance/sessions',
        description: 'Lớp gói thường có học viên trong ngày. Buổi Private không điểm danh. HLV chỉ thấy lớp của mình. Quyền `attendance.view`.',
        summary: 'Danh sách buổi cần điểm danh',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Attendance'],
        parameters: [
            new OA\Parameter(name: 'date', description: 'Mặc định hôm nay', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAttendanceSession')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function sessions(Request $request): JsonResponse
    {
        $validated = $request->validate(['date' => 'nullable|date_format:Y-m-d']);
        /** @var User $viewer */
        $viewer = auth()->user();

        return ResponseAPI::success($this->buildSessions(
            ClassSchedule::query()->whereDate('start_time', $validated['date'] ?? now()->toDateString()),
            $viewer,
        ));
    }

    #[OA\Post(
        path: '/admin/attendance/records',
        description: 'Ghi hoặc sửa điểm danh, chỉ trong ngày diễn ra buổi học. Không trừ buổi. HLV chỉ thao tác trên lớp của mình. Quyền `attendance.mark`.',
        summary: 'Điểm danh một học viên',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['booking_id', 'status'], properties: [
            new OA\Property(property: 'booking_id', type: 'integer', example: 21),
            new OA\Property(property: 'status', type: 'string', enum: ['present', 'absent'], example: 'present'),
        ])),
        tags: ['Gym Admin Attendance'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAttendanceSession'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không phải lớp của HLV này'),
            new OA\Response(response: 422, description: 'Ngoài ngày học hoặc booking đã hủy'),
        ]
    )]
    public function mark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'status' => ['required', Rule::in(AttendanceStatus::getValues())],
        ]);
        /** @var User $staff */
        $staff = auth()->user();
        /** @var Booking $booking */
        $booking = Booking::query()->with('classSchedule')->findOrFail($validated['booking_id']);
        $this->assertCanAccess($booking->classSchedule, $staff);

        $this->attendanceService->mark($booking, $validated['status'], $staff);

        return ResponseAPI::success($this->sessionPayload($booking->classSchedule, $staff));
    }

    #[OA\Post(
        path: '/admin/attendance/sessions/{schedule}/mark-all-present',
        description: 'Đánh dấu mọi học viên trong lớp là có mặt, chỉ trong ngày diễn ra buổi học. Quyền `attendance.mark`.',
        summary: 'Có mặt tất cả',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Attendance'],
        parameters: [new OA\Parameter(name: 'schedule', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAttendanceSession'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không phải lớp của HLV này'),
            new OA\Response(response: 422, description: 'Ngoài ngày học'),
        ]
    )]
    public function markAllPresent(ClassSchedule $schedule): JsonResponse
    {
        /** @var User $staff */
        $staff = auth()->user();
        $this->assertCanAccess($schedule, $staff);

        $this->attendanceService->markAllPresent($schedule, $staff);

        return ResponseAPI::success($this->sessionPayload($schedule, $staff));
    }

    private function assertCanAccess(ClassSchedule $schedule, User $user): void
    {
        abort_if($user->role->is(Role::Trainer) && $schedule->trainer_id !== $user->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(ClassSchedule $schedule, User $viewer): array
    {
        return $this->buildSessions(ClassSchedule::query()->whereKey($schedule->id), $viewer)[0];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSessions($query, User $viewer): array
    {
        $schedules = $query
            ->with(['fitnessClass', 'trainer', 'confirmedBookings.user', 'confirmedBookings.userPackage', 'confirmedBookings.attendanceRecord'])
            ->where('status', ClassScheduleStatus::Active)
            ->whereHas('confirmedBookings')
            ->when($viewer->role->is(Role::Trainer), fn ($inner) => $inner->where('trainer_id', $viewer->id))
            ->orderBy('start_time')
            ->get();

        $absences = $this->attendanceService->recentAbsences($schedules->flatMap(fn (ClassSchedule $schedule) => $schedule->confirmedBookings->pluck('user_id'))->unique());

        return $schedules->map(fn (ClassSchedule $schedule) => [
            'id' => $schedule->id,
            'class_name' => $schedule->fitnessClass->name,
            'trainer_id' => $schedule->trainer_id,
            'trainer_name' => trim($schedule->trainer->first_name.' '.$schedule->trainer->last_name),
            'room' => $schedule->room,
            'start_time' => $schedule->start_time->format('Y-m-d\TH:i:s'),
            'end_time' => $schedule->end_time->format('Y-m-d\TH:i:s'),
            'editable' => $this->attendanceService->isMarkable($schedule),
            'members' => $schedule->confirmedBookings->map(fn (Booking $booking) => [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'member_name' => trim($booking->user->first_name.' '.$booking->user->last_name),
                'package_name' => $booking->userPackage->name,
                'absent_recent' => $absences[$booking->user_id] ?? 0,
                'attendance_status' => $booking->attendanceRecord?->status,
                'has_redlight' => $booking->has_redlight,
            ])->all(),
        ])->all();
    }
}
