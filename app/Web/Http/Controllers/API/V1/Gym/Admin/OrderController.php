<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Models\Order;
use App\Share\Models\User;
use App\Share\Services\Gym\OrderService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsOrder;
use App\Web\Http\Requests\Gym\OrderProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class OrderController extends BaseAPIController
{
    use MapsOrder;

    public function __construct(private readonly OrderService $orderService) {}

    #[OA\Post(
        path: '/admin/users/{user}/orders',
        description: 'Nhân viên tạo đơn hộ hội viên. Khách đăng nhập tài khoản của mình để ký và thanh toán như đơn tự đặt. Quyền `orders.create`.',
        summary: 'Admin: tạo đơn hộ hội viên',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['type'],
            properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['package', 'therapy'], example: 'package'),
                new OA\Property(property: 'package_id', description: 'Bắt buộc khi type=package', type: 'integer', example: 3),
                new OA\Property(property: 'therapy_service_id', description: 'Bắt buộc khi type=therapy', type: 'integer', example: 1),
                new OA\Property(property: 'therapy_variant', description: 'Bắt buộc khi type=therapy', type: 'string', enum: ['single', 'combo5'], example: 'single'),
                new OA\Property(property: 'note', description: 'Ghi chú nội bộ', type: 'string', nullable: true),
            ]
        )),
        tags: ['Gym Admin Orders'],
        parameters: [
            new OA\Parameter(name: 'user', description: 'ID hội viên', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 4)),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Đã tạo', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminOrder'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ, gói không mở bán hoặc không phải hội viên'),
        ]
    )]
    public function store(OrderProductRequest $request, User $user): JsonResponse
    {
        /** @var User $staff */
        $staff = auth()->user();
        $product = $request->safe()->except('note');

        $order = $this->orderService->create($user, $product, $staff, $request->validated('note'));

        return ResponseAPI::success($this->mapAdminOrder($order->refresh()->load(['user', 'creator'])), code: 201);
    }

    #[OA\Get(
        path: '/admin/payments',
        description: 'Danh sách đơn hàng và giao dịch, mới nhất trước. Quyền `payments.manage`.',
        summary: 'Admin: danh sách giao dịch',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Orders'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Tìm theo mã đơn, mã giao dịch, tên hoặc email học viên', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending_terms', 'pending', 'success', 'cancelled'])),
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['package', 'therapy'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAdminOrder')),
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
            'status' => ['nullable', Rule::in(OrderStatus::getValues())],
            'type' => ['nullable', Rule::in(OrderType::getValues())],
        ]);

        $orders = Order::query()
            ->with(['user', 'creator'])
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->where('code', 'like', $like)
                    ->orWhere('transaction_code', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('email', 'like', $like)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])));
            })
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($orders->map(fn (Order $order) => $this->mapAdminOrder($order))->all());
    }

    #[OA\Get(
        path: '/admin/payments/{order}',
        description: 'Chi tiết đơn, gồm ảnh chữ ký. Quyền `payments.manage`.',
        summary: 'Admin: chi tiết giao dịch',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Orders'],
        parameters: [
            new OA\Parameter(name: 'order', description: 'ID đơn', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 12)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', allOf: [
                    new OA\Schema(ref: '#/components/schemas/GymAdminOrder'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'terms_signature', description: 'Ảnh chữ ký dạng data URL', type: 'string', nullable: true),
                    ]),
                ]),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(Order $order): JsonResponse
    {
        return ResponseAPI::success([
            ...$this->mapAdminOrder($order->load(['user', 'creator'])),
            'terms_signature' => $order->terms_signature,
        ]);
    }
}
