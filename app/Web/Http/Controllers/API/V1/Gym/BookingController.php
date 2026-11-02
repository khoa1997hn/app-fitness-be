<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use App\Share\Services\Gym\BookingService;
use App\Share\Services\Gym\RedlightService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymMemberSchedule',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymClassSchedule'),
        new OA\Schema(properties: [
            new OA\Property(property: 'is_full', type: 'boolean', example: false),
            new OA\Property(property: 'is_booked', description: 'Tôi đã đặt buổi này', type: 'boolean', example: false),
            new OA\Property(property: 'redlight_available', description: 'Lớp Barre, Pilates chưa bắt đầu', type: 'boolean', example: true),
            new OA\Property(property: 'redlight_price', type: 'integer', example: 30000),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'GymMemberBooking',
    required: ['id', 'class_schedule_id', 'status', 'can_cancel'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 21),
        new OA\Property(property: 'class_schedule_id', type: 'integer', example: 15),
        new OA\Property(property: 'class_name', type: 'string', example: 'Yoga Flow'),
        new OA\Property(property: 'category', type: 'string', example: 'yoga-sound'),
        new OA\Property(property: 'trainer_name', type: 'string', example: 'Nguyễn Thu Hà'),
        new OA\Property(property: 'room', type: 'string', example: 'P101'),
        new OA\Property(property: 'start_time', type: 'string', example: '2026-11-05T07:00:00'),
        new OA\Property(property: 'end_time', type: 'string', example: '2026-11-05T08:00:00'),
        new OA\Property(property: 'package_name', type: 'string', example: '10 classes'),
        new OA\Property(property: 'status', description: '`booked` còn hủy được, `locked` trong vòng 8 tiếng, `completed` đã qua giờ', type: 'string', enum: ['booked', 'locked', 'completed'], example: 'booked'),
        new OA\Property(property: 'can_cancel', type: 'boolean', example: true),
        new OA\Property(property: 'has_redlight', description: 'Đã trả tiền Redlight cho buổi này', type: 'boolean', example: false),
        new OA\Property(property: 'redlight_available', description: 'Còn mua thêm Redlight được', type: 'boolean', example: true),
        new OA\Property(property: 'redlight_price', type: 'integer', example: 30000),
        new OA\Property(property: 'redlight_order_code', description: 'Đơn Redlight đang chờ thanh toán', type: 'string', nullable: true),
    ]
)]
class BookingController extends BaseAPIController
{
    use MapsSchedule;

    public function __construct(
        private readonly BookingService $bookingService,
        private readonly RedlightService $redlightService,
    ) {}

    #[OA\Get(
        path: '/schedules',
        description: 'Thời khóa biểu còn đặt được. Có `user_package_id` của gói off-peak thì chỉ trả lớp trong khung off-peak.',
        summary: 'Danh sách lớp để đặt',
        security: [['bearerAuth' => []]],
        tags: ['Gym Bookings'],
        parameters: [
            new OA\Parameter(name: 'user_package_id', description: 'Gói đang chọn', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', description: 'Lọc theo ngày, không truyền thì lấy từ hiện tại trở đi', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['sculpt', 'core-pilates', 'yoga-sound', 'barre', 'meditation-sound', 'dance-cardio'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymMemberSchedule')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function schedules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_package_id' => 'nullable|integer',
            'date' => 'nullable|date_format:Y-m-d',
            'category' => ['nullable', Rule::in(FitnessClassCategory::getValues())],
        ]);

        $package = isset($validated['user_package_id'])
            ? UserPackage::query()->where('user_id', auth()->id())->findOrFail($validated['user_package_id'])
            : null;

        $schedules = ClassSchedule::query()
            ->with(['fitnessClass', 'trainer'])
            ->withCount('confirmedBookings')
            ->where('status', ClassScheduleStatus::Active)
            ->where('start_time', '>', now())
            ->when($validated['date'] ?? null, fn ($query, string $date) => $query->whereDate('start_time', $date))
            ->when($validated['category'] ?? null, fn ($query, string $category) => $query
                ->whereHas('fitnessClass', fn ($class) => $class->where('category', $category)))
            ->when($package?->is_off_peak, fn ($query) => $query->where(fn ($window) => $window
                ->whereTime('start_time', '<=', config('gym.off_peak_morning_end'))
                ->orWhereTime('start_time', '>=', config('gym.off_peak_evening_start'))))
            ->orderBy('start_time')
            ->get();

        $bookedIds = $this->bookedScheduleIds($schedules->pluck('id')->all());

        return ResponseAPI::success($schedules
            ->map(fn (ClassSchedule $schedule) => $this->mapMemberSchedule($schedule, in_array($schedule->id, $bookedIds, true)))
            ->all());
    }

    #[OA\Get(
        path: '/schedules/{schedule}',
        description: 'Chi tiết một buổi trên thời khóa biểu.',
        summary: 'Chi tiết lớp',
        security: [['bearerAuth' => []]],
        tags: ['Gym Bookings'],
        parameters: [
            new OA\Parameter(name: 'schedule', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymMemberSchedule'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function schedule(ClassSchedule $schedule): JsonResponse
    {
        $schedule->load(['fitnessClass', 'trainer'])->loadCount('confirmedBookings');

        return ResponseAPI::success(
            $this->mapMemberSchedule($schedule, $this->bookedScheduleIds([$schedule->id]) !== [])
        );
    }

    #[OA\Get(
        path: '/users/me/bookings',
        description: 'Các buổi đã đặt còn hiệu lực, gồm cả buổi đã qua giờ.',
        summary: 'Lịch đã đặt của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Bookings'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymMemberBooking')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function index(): JsonResponse
    {
        $bookings = Booking::query()
            ->with(['classSchedule.fitnessClass', 'classSchedule.trainer', 'userPackage', 'pendingRedlightOrder'])
            ->where('user_id', auth()->id())
            ->where('status', BookingStatus::Confirmed)
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($bookings->map(fn (Booking $booking) => $this->mapBooking($booking))->all());
    }

    #[OA\Post(
        path: '/bookings',
        description: 'Đặt một buổi bằng gói thường. Chưa trừ buổi, buổi bị trừ sau giờ học kể cả khi vắng.',
        summary: 'Đặt lịch',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['class_schedule_id', 'user_package_id'],
            properties: [
                new OA\Property(property: 'class_schedule_id', type: 'integer', example: 15),
                new OA\Property(property: 'user_package_id', type: 'integer', example: 7),
            ]
        )),
        tags: ['Gym Bookings'],
        responses: [
            new OA\Response(response: 201, description: 'Đã đặt', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymMemberBooking'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 404, description: 'Không tìm thấy gói hoặc lớp'),
            new OA\Response(response: 422, description: 'Hết chỗ, hết buổi, gói hết hạn, ngoài khung off-peak, đã đặt rồi, hoặc gói Private'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_schedule_id' => 'required|integer',
            'user_package_id' => 'required|integer',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $schedule = ClassSchedule::query()->findOrFail($validated['class_schedule_id']);

        $booking = $this->bookingService->book($user, $validated['user_package_id'], $schedule);

        return ResponseAPI::success($this->mapBooking($booking->load(['classSchedule.fitnessClass', 'classSchedule.trainer', 'userPackage', 'pendingRedlightOrder'])), code: 201);
    }

    #[OA\Patch(
        path: '/bookings/{booking}/cancel',
        description: 'Hủy lịch trước giờ học tối thiểu 8 tiếng, không mất buổi. Trong vòng 8 tiếng bị chặn.',
        summary: 'Hủy lịch',
        security: [['bearerAuth' => []]],
        tags: ['Gym Bookings'],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 21)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã hủy', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'string', example: null, nullable: true),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Trong vòng 8 tiếng hoặc đã hủy'),
        ]
    )]
    public function cancel(Booking $booking): JsonResponse
    {
        abort_unless($booking->user_id === auth()->id(), 404);

        /** @var User $user */
        $user = auth()->user();
        $this->bookingService->cancelByMember($booking, $user);

        return ResponseAPI::success();
    }

    /**
     * @param  list<int>  $scheduleIds
     * @return list<int>
     */
    private function bookedScheduleIds(array $scheduleIds): array
    {
        return Booking::query()
            ->where('user_id', auth()->id())
            ->where('status', BookingStatus::Confirmed)
            ->whereIn('class_schedule_id', $scheduleIds)
            ->pluck('class_schedule_id')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMemberSchedule(ClassSchedule $schedule, bool $isBooked): array
    {
        return [
            ...$this->mapSchedule($schedule),
            'is_full' => $schedule->confirmed_bookings_count >= $schedule->max_slot,
            'is_booked' => $isBooked,
            'redlight_available' => $this->redlightService->isAvailable($schedule),
            'redlight_price' => (int) config('gym.redlight_price'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBooking(Booking $booking): array
    {
        $schedule = $booking->classSchedule;
        $canCancel = $this->bookingService->canMemberCancel($schedule);

        return [
            'id' => $booking->id,
            'class_schedule_id' => $schedule->id,
            'class_name' => $schedule->fitnessClass->name,
            'category' => $schedule->fitnessClass->category,
            'trainer_name' => trim($schedule->trainer->first_name.' '.$schedule->trainer->last_name),
            'room' => $schedule->room,
            'start_time' => $schedule->start_time->format('Y-m-d\TH:i:s'),
            'end_time' => $schedule->end_time->format('Y-m-d\TH:i:s'),
            'package_name' => $booking->userPackage->name,
            'status' => $schedule->end_time->isPast() ? 'completed' : ($canCancel ? 'booked' : 'locked'),
            'can_cancel' => $canCancel,
            'has_redlight' => $booking->redlight_paid_at !== null,
            'redlight_available' => $booking->redlight_paid_at === null && $this->redlightService->isAvailable($schedule),
            'redlight_price' => (int) config('gym.redlight_price'),
            'redlight_order_code' => $booking->pendingRedlightOrder?->code,
        ];
    }
}
