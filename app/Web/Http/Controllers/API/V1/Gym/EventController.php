<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Enums\EventStatus;
use App\Share\Models\Event;
use App\Share\Models\EventRegistration;
use App\Share\Models\User;
use App\Share\Services\Gym\EventService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class EventController extends BaseAPIController
{
    use MapsEvent;

    public function __construct(private readonly EventService $eventService) {}

    #[OA\Get(
        path: '/events',
        description: 'Sự kiện đang mở và chưa diễn ra, gần nhất trước. Công khai, không cần đăng nhập.',
        summary: 'Danh sách sự kiện',
        tags: ['Gym Events'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymEvent')),
            ])),
        ]
    )]
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->withTicketCounts()
            ->where('status', EventStatus::Open)
            ->where('event_date', '>', now())
            ->orderBy('event_date')
            ->get();

        return ResponseAPI::success($events->map(fn (Event $event) => $this->mapEvent($event))->all());
    }

    #[OA\Get(
        path: '/events/{event}',
        description: 'Chi tiết sự kiện. Công khai.',
        summary: 'Chi tiết sự kiện',
        tags: ['Gym Events'],
        parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymEvent'),
            ])),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(int $event): JsonResponse
    {
        return ResponseAPI::success($this->mapEvent(Event::query()->withTicketCounts()->findOrFail($event)));
    }

    #[OA\Post(
        path: '/events/{event}/register',
        description: 'Đăng ký vé miễn phí, có mã vé ngay. Sự kiện có phí tạo đơn qua `POST /orders` với `type=event`.',
        summary: 'Đăng ký vé miễn phí',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [
            new OA\Property(property: 'quantity', type: 'integer', minimum: 1, maximum: 20, example: 1),
        ])),
        tags: ['Gym Events'],
        parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 201, description: 'Đã đăng ký', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymEventRegistration'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Sự kiện có phí, đã đóng, hết vé, hoặc đã đăng ký'),
        ]
    )]
    public function register(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate(['quantity' => 'nullable|integer|min:1|max:1000']);
        /** @var User $member */
        $member = auth()->user();

        $registration = $this->eventService->registerFree($event, $member, (int) ($validated['quantity'] ?? 1));

        return ResponseAPI::success($this->mapRegistration($registration->load('order')), code: 201);
    }

    #[OA\Get(
        path: '/users/me/event-registrations',
        description: 'Vé sự kiện của tôi, mới nhất trước, gồm cả vé đã hủy.',
        summary: 'Vé sự kiện của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Events'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymEventRegistration')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function myRegistrations(): JsonResponse
    {
        $registrations = EventRegistration::query()
            ->with(['order', 'event' => fn ($query) => $query->withTicketCounts()])
            ->where('user_id', auth()->id())
            ->latest('id')
            ->get();

        return ResponseAPI::success($registrations->map(fn (EventRegistration $registration) => [
            ...$this->mapRegistration($registration),
            'event' => $this->mapEvent($registration->event),
        ])->all());
    }
}
