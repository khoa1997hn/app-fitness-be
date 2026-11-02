<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\EventStatus;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\Event;
use App\Share\Models\EventRegistration;
use App\Share\Models\User;
use App\Share\Services\Gym\EventService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymEventInput',
    required: ['name', 'event_date', 'location', 'ticket_price', 'total_tickets'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Workshop Pilates chuyên sâu'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'event_date', type: 'string', example: '2026-12-20T09:00'),
        new OA\Property(property: 'location', type: 'string', example: 'Cơ sở Hải Phòng'),
        new OA\Property(property: 'ticket_price', description: '0 là miễn phí', type: 'integer', example: 300000),
        new OA\Property(property: 'total_tickets', type: 'integer', example: 30),
    ]
)]
#[OA\Schema(
    schema: 'GymAdminEvent',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymEvent'),
        new OA\Schema(properties: [
            new OA\Property(property: 'held_tickets', description: 'Vé đang giữ chờ thanh toán', type: 'integer', example: 2),
            new OA\Property(property: 'revenue', description: 'Tổng đơn đã thanh toán', type: 'integer', example: 3600000),
            new OA\Property(property: 'cancel_reason', type: 'string', nullable: true),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'GymAdminEventRegistration',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymEventRegistration'),
        new OA\Schema(properties: [
            new OA\Property(property: 'user_id', type: 'integer', example: 7),
            new OA\Property(property: 'member_name', type: 'string', example: 'Phạm Thị Member'),
            new OA\Property(property: 'member_phone', type: 'string', nullable: true),
            new OA\Property(property: 'member_email', type: 'string', format: 'email'),
            new OA\Property(property: 'needs_refund', description: 'Vé đã thanh toán của sự kiện bị hủy, cần hoàn tiền thủ công', type: 'boolean', example: false),
        ]),
    ]
)]
class EventController extends BaseAPIController
{
    use MapsEvent;

    public function __construct(private readonly EventService $eventService) {}

    #[OA\Get(
        path: '/admin/events',
        description: 'Mọi sự kiện, mới nhất trước. Quyền `events.manage`.',
        summary: 'Admin: danh sách sự kiện',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Events'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Tìm theo tên, địa điểm', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['open', 'closed', 'cancelled'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAdminEvent')),
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
            'status' => ['nullable', Rule::in(EventStatus::getValues())],
        ]);

        $events = Event::query()
            ->withTicketCounts()
            ->withRevenue()
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->whereTranslationLike('name', $like)
                    ->orWhere('location', 'like', $like));
            })
            ->orderByDesc('event_date')
            ->get();

        return ResponseAPI::success($events->map(fn (Event $event) => $this->mapAdminEvent($event))->all());
    }

    #[OA\Post(
        path: '/admin/events',
        description: 'Tạo sự kiện, mở bán ngay. Ngày phải ở tương lai. Quyền `events.manage`.',
        summary: 'Admin: tạo sự kiện',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GymEventInput')),
        tags: ['Gym Admin Events'],
        responses: [
            new OA\Response(response: 201, description: 'Đã tạo', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminEvent'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateEvent($request);

        if (Carbon::parse($validated['event_date'])->isPast()) {
            throw new GymException(__('messages.gym_event_date_future'));
        }

        $event = Event::query()->create([
            ...$this->eventAttributes($validated),
            'status' => EventStatus::Open,
        ]);

        return ResponseAPI::success($this->adminPayload($event->id), code: 201);
    }

    #[OA\Get(
        path: '/admin/events/{event}',
        description: 'Chi tiết sự kiện. Quyền `events.manage`.',
        summary: 'Admin: chi tiết sự kiện',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Events'],
        parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminEvent'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(Event $event): JsonResponse
    {
        return ResponseAPI::success($this->adminPayload($event->id));
    }

    #[OA\Patch(
        path: '/admin/events/{event}',
        description: 'Sửa sự kiện. Số vé không được nhỏ hơn số đã bán và đang giữ. Đổi ngày thì ngày mới phải ở tương lai. Sự kiện đã hủy không sửa được. Quyền `events.manage`.',
        summary: 'Admin: sửa sự kiện',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GymEventInput')),
        tags: ['Gym Admin Events'],
        parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminEvent'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $this->validateEvent($request);

        if ($event->status->is(EventStatus::Cancelled)) {
            throw new GymException(__('messages.gym_event_cancelled_locked'));
        }

        $date = Carbon::parse($validated['event_date']);

        if (! $date->equalTo($event->event_date) && $date->isPast()) {
            throw new GymException(__('messages.gym_event_date_future'));
        }

        $this->eventService->assertTotalTicketsValid($event, (int) $validated['total_tickets']);
        $event->update($this->eventAttributes($validated));

        return ResponseAPI::success($this->adminPayload($event->id));
    }

    #[OA\Patch(
        path: '/admin/events/{event}/status',
        description: 'Mở lại, đóng hoặc hủy sự kiện. Hủy là trạng thái cuối: mọi vé bị hủy, hội viên nhận thông báo, vé đã thanh toán hoàn tiền thủ công. Quyền `events.manage`.',
        summary: 'Admin: đổi trạng thái sự kiện',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['status'], properties: [
            new OA\Property(property: 'status', type: 'string', enum: ['open', 'closed', 'cancelled'], example: 'closed'),
            new OA\Property(property: 'reason', description: 'Lý do hủy, gửi kèm thông báo', type: 'string', nullable: true),
        ])),
        tags: ['Gym Admin Events'],
        parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminEvent'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Không chuyển được sang trạng thái này'),
        ]
    )]
    public function updateStatus(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(EventStatus::getValues())],
            'reason' => 'nullable|string|max:1000',
        ]);

        $this->eventService->changeStatus($event, $validated['status'], $validated['reason'] ?? null);

        return ResponseAPI::success($this->adminPayload($event->id));
    }

    #[OA\Get(
        path: '/admin/events/{event}/registrations',
        description: 'Người đăng ký của sự kiện, mới nhất trước. Quyền `events.manage`.',
        summary: 'Admin: danh sách người đăng ký',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Events'],
        parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAdminEventRegistration')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function registrations(Event $event): JsonResponse
    {
        $registrations = $event->registrations()->with(['user', 'order'])->latest('id')->get();

        return ResponseAPI::success($registrations->map(fn (EventRegistration $registration) => $this->mapAdminRegistration($registration))->all());
    }

    #[OA\Patch(
        path: '/admin/registrations/{registration}/checkin',
        description: 'Check-in vé đã thanh toán, mỗi vé một lần. Quyền `events.manage`.',
        summary: 'Admin: check-in vé',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Events'],
        parameters: [new OA\Parameter(name: 'registration', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminEventRegistration'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Vé chưa thanh toán hoặc đã check-in'),
        ]
    )]
    public function checkin(EventRegistration $registration): JsonResponse
    {
        /** @var User $staff */
        $staff = auth()->user();
        $checkedIn = $this->eventService->checkIn($registration, $staff);

        return ResponseAPI::success($this->mapAdminRegistration($checkedIn->load(['user', 'order'])));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'ticket_price' => 'required|integer|min:0|max:1000000000',
            'total_tickets' => 'required|integer|min:1|max:100000',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function eventAttributes(array $validated): array
    {
        return [
            'event_date' => Carbon::parse($validated['event_date']),
            'location' => $validated['location'],
            'ticket_price' => $validated['ticket_price'],
            'total_tickets' => $validated['total_tickets'],
            'vi' => ['name' => $validated['name'], 'description' => $validated['description'] ?? null],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminPayload(int $eventId): array
    {
        return $this->mapAdminEvent(Event::query()->withTicketCounts()->withRevenue()->findOrFail($eventId));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapAdminEvent(Event $event): array
    {
        return [
            ...$this->mapEvent($event),
            'revenue' => (int) $event->revenue,
            'cancel_reason' => $event->cancel_reason,
        ];
    }
}
