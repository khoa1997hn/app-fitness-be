<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\Role;
use App\Share\Models\ClassSchedule;
use App\Share\Models\FitnessClass;
use App\Share\Models\User;
use App\Share\Services\Gym\BookingService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsSchedule;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymTimetableInput',
    required: ['fitness_class_id', 'trainer_id', 'room', 'start_time', 'end_time', 'max_slot', 'status'],
    properties: [
        new OA\Property(property: 'fitness_class_id', type: 'integer', example: 8),
        new OA\Property(property: 'trainer_id', description: 'User có role trainer', type: 'integer', example: 3),
        new OA\Property(property: 'room', type: 'string', example: 'P101'),
        new OA\Property(property: 'start_time', type: 'string', example: '2026-11-05T07:00'),
        new OA\Property(property: 'end_time', description: 'Sau start_time', type: 'string', example: '2026-11-05T08:00'),
        new OA\Property(property: 'max_slot', type: 'integer', example: 15),
        new OA\Property(property: 'status', description: 'Đổi sang cancelled sẽ hủy các booking, không trừ buổi', type: 'string', enum: ['active', 'cancelled'], example: 'active'),
        new OA\Property(property: 'notes', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'GymAdminClassSchedule',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymClassSchedule'),
        new OA\Schema(properties: [new OA\Property(property: 'notes', type: 'string', nullable: true)]),
    ]
)]
class TimetableController extends BaseAPIController
{
    use MapsSchedule;

    public function __construct(private readonly BookingService $bookingService) {}

    #[OA\Get(
        path: '/admin/timetable',
        description: 'Thời khóa biểu, mới nhất trước. Quyền `schedules.timetable.manage`.',
        summary: 'Admin: danh sách thời khóa biểu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Timetable'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Tìm theo lớp, HLV, phòng', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'cancelled'])),
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAdminClassSchedule')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(ClassScheduleStatus::getValues())],
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $schedules = ClassSchedule::query()
            ->with(['fitnessClass', 'trainer'])
            ->withCount('confirmedBookings')
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['date'] ?? null, fn ($query, string $date) => $query->whereDate('start_time', $date))
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->where('room', 'like', $like)
                    ->orWhereHas('fitnessClass', fn ($class) => $class->where('name', 'like', $like))
                    ->orWhereHas('trainer', fn ($trainer) => $trainer->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])));
            })
            ->orderByDesc('start_time')
            ->get();

        return ResponseAPI::success($schedules->map(fn (ClassSchedule $schedule) => $this->mapAdminSchedule($schedule))->all());
    }

    #[OA\Get(
        path: '/admin/timetable/options',
        description: 'Dữ liệu cho form: lớp, HLV, phòng. Quyền `schedules.timetable.manage`.',
        summary: 'Admin: lựa chọn của form thời khóa biểu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Timetable'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'classes', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 8),
                        new OA\Property(property: 'name', type: 'string', example: 'Yoga Flow'),
                        new OA\Property(property: 'category', type: 'string', example: 'yoga-sound'),
                    ], type: 'object')),
                    new OA\Property(property: 'trainers', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 3),
                        new OA\Property(property: 'name', type: 'string', example: 'Nguyễn Thu Hà'),
                    ], type: 'object')),
                    new OA\Property(property: 'rooms', type: 'array', items: new OA\Items(type: 'string', example: 'P101')),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function options(): JsonResponse
    {
        return ResponseAPI::success([
            'classes' => FitnessClass::query()->orderBy('id')->get()->map(fn (FitnessClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'category' => $class->category,
            ])->all(),
            'trainers' => User::query()->where('role', Role::Trainer)->orderBy('first_name')->get()->map(fn (User $trainer) => [
                'id' => $trainer->id,
                'name' => trim($trainer->first_name.' '.$trainer->last_name),
            ])->all(),
            'rooms' => config('gym.rooms'),
        ]);
    }

    #[OA\Get(
        path: '/admin/timetable/{schedule}',
        description: 'Chi tiết lịch. Quyền `schedules.timetable.manage`.',
        summary: 'Admin: chi tiết lịch',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Timetable'],
        parameters: [
            new OA\Parameter(name: 'schedule', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminClassSchedule'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(ClassSchedule $schedule): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminSchedule($this->loadSchedule($schedule)));
    }

    #[OA\Post(
        path: '/admin/timetable',
        description: 'Thêm lịch. Giờ bắt đầu phải ở tương lai, trạng thái phải là `active`. Quyền `schedules.timetable.manage`.',
        summary: 'Admin: thêm lịch',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GymTimetableInput')),
        tags: ['Gym Admin Timetable'],
        responses: [
            new OA\Response(response: 201, description: 'Đã tạo', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminClassSchedule'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $schedule = ClassSchedule::query()->create($this->validateSchedule($request));

        return ResponseAPI::success($this->mapAdminSchedule($this->loadSchedule($schedule)), code: 201);
    }

    #[OA\Patch(
        path: '/admin/timetable/{schedule}',
        description: 'Sửa lịch. Đổi giờ bắt đầu thì giờ mới phải ở tương lai. `max_slot` không được nhỏ hơn số đã đặt. Đổi sang `cancelled` sẽ hủy các booking, không trừ buổi. Quyền `schedules.timetable.manage`.',
        summary: 'Admin: sửa lịch',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GymTimetableInput')),
        tags: ['Gym Admin Timetable'],
        parameters: [
            new OA\Parameter(name: 'schedule', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã cập nhật', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminClassSchedule'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(Request $request, ClassSchedule $schedule): JsonResponse
    {
        $validated = $this->validateSchedule($request, $schedule);
        $isCancelling = $validated['status'] === ClassScheduleStatus::Cancelled
            && ! $schedule->status->is(ClassScheduleStatus::Cancelled);

        $schedule->update($validated);

        if ($isCancelling) {
            /** @var User $staff */
            $staff = auth()->user();
            $this->bookingService->cancelSchedule($schedule, $staff);
        }

        return ResponseAPI::success($this->mapAdminSchedule($this->loadSchedule($schedule->refresh())));
    }

    #[OA\Patch(
        path: '/admin/timetable/{schedule}/cancel',
        description: 'Hủy lớp và các booking, không trừ buổi của học viên. Quyền `schedules.timetable.manage`.',
        summary: 'Admin: hủy lớp',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Timetable'],
        parameters: [
            new OA\Parameter(name: 'schedule', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã hủy', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminClassSchedule'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function cancel(ClassSchedule $schedule): JsonResponse
    {
        /** @var User $staff */
        $staff = auth()->user();
        $this->bookingService->cancelSchedule($schedule, $staff);

        return ResponseAPI::success($this->mapAdminSchedule($this->loadSchedule($schedule->refresh())));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSchedule(Request $request, ?ClassSchedule $schedule = null): array
    {
        $minSlot = max(1, $schedule?->confirmedBookings()->count() ?? 0);

        return $request->validate([
            'fitness_class_id' => 'required|integer|exists:fitness_classes,id',
            'trainer_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', Role::Trainer)->whereNull('deleted_at')],
            'room' => ['required', Rule::in(config('gym.rooms'))],
            'start_time' => ['bail', 'required', 'date', function (string $attribute, mixed $value, Closure $fail) use ($schedule) {
                $startTime = Carbon::parse($value);

                // Sửa lớp đã diễn ra mà giữ nguyên giờ thì vẫn được
                if ($startTime->isPast() && ! $schedule?->start_time->eq($startTime)) {
                    $fail(__('messages.gym_schedule_start_in_past'));
                }
            }],
            'end_time' => 'required|date|after:start_time',
            'max_slot' => "required|integer|min:{$minSlot}|max:65535",
            'status' => ['required', Rule::in($schedule ? ClassScheduleStatus::getValues() : [ClassScheduleStatus::Active])],
            'notes' => 'nullable|string|max:1000',
        ]);
    }

    private function loadSchedule(ClassSchedule $schedule): ClassSchedule
    {
        return $schedule->load(['fitnessClass', 'trainer'])->loadCount('confirmedBookings');
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAdminSchedule(ClassSchedule $schedule): array
    {
        return [...$this->mapSchedule($schedule), 'notes' => $schedule->notes];
    }
}
