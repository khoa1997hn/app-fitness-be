<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Enums\PaymentMethod;
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

    /** Ảnh chữ ký dạng data URL, giới hạn để không làm phình bảng orders */
    private const SIGNATURE_MAX_LENGTH = 700000;

    public function __construct(private readonly OrderService $orderService) {}

    #[OA\Get(
        path: '/users/me/orders',
        description: 'Đơn hàng của tôi, mới nhất trước, gồm cả đơn nhân viên tạo hộ đang chờ ký hoặc thanh toán.',
        summary: 'Đơn hàng của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymOrder')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function index(): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($orders->map(fn (Order $order) => $this->mapOrder($order))->all());
    }

    #[OA\Post(
        path: '/orders',
        description: 'Hội viên tự tạo đơn. Giá lấy từ server. Đơn bắt đầu ở `pending_terms`, riêng `extra_people` vào thẳng `pending`.',
        summary: 'Tạo đơn hàng',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['type'],
            properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['package', 'therapy', 'extra_people', 'event', 'redlight'], example: 'package'),
                new OA\Property(property: 'package_id', description: 'Bắt buộc khi type=package', type: 'integer', example: 3),
                new OA\Property(property: 'therapy_service_id', description: 'Bắt buộc khi type=therapy', type: 'integer', example: 1),
                new OA\Property(property: 'therapy_variant', description: 'Bắt buộc khi type=therapy', type: 'string', enum: ['single', 'combo5'], example: 'single'),
                new OA\Property(property: 'event_id', description: 'Bắt buộc khi type=event', type: 'integer', example: 2),
                new OA\Property(property: 'booking_id', description: 'Bắt buộc khi type=redlight', type: 'integer', example: 21),
                new OA\Property(property: 'quantity', description: 'Bắt buộc khi type=extra_people hoặc event', type: 'integer', example: 5),
            ]
        )),
        tags: ['Gym Orders'],
        responses: [
            new OA\Response(response: 201, description: 'Đã tạo', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymOrder'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc gói không còn mở bán'),
        ]
    )]
    public function store(OrderProductRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $order = $this->orderService->create($user, $request->safe()->except('note'));

        return ResponseAPI::success($this->mapOrder($order->refresh()), code: 201);
    }

    #[OA\Get(
        path: '/orders/{code}',
        description: 'Chi tiết đơn của chính mình. Đơn của người khác trả 404.',
        summary: 'Chi tiết đơn hàng',
        security: [['bearerAuth' => []]],
        tags: ['Gym Orders'],
        parameters: [
            new OA\Parameter(name: 'code', description: 'Mã đơn', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'GYM-7K2M9QXA1B')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymOrder'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(Order $order): JsonResponse
    {
        $this->ensureOwner($order);

        return ResponseAPI::success($this->mapOrder($order));
    }

    #[OA\Post(
        path: '/orders/{code}/accept-terms',
        description: 'Xác nhận điều khoản và ký ảo, đơn chuyển sang `pending`.',
        summary: 'Ký điều khoản',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['terms_accepted', 'signature_data', 'buyer_name', 'buyer_phone', 'buyer_email'],
            properties: [
                new OA\Property(property: 'terms_accepted', type: 'boolean', example: true),
                new OA\Property(property: 'signature_data', description: 'Ảnh chữ ký PNG dạng data URL', type: 'string', example: 'data:image/png;base64,iVBOR...'),
                new OA\Property(property: 'buyer_name', type: 'string', example: 'Phạm Thị Member'),
                new OA\Property(property: 'buyer_phone', type: 'string', example: '0900000004'),
                new OA\Property(property: 'buyer_email', type: 'string', format: 'email', example: 'member@demo.kore.vn'),
            ]
        )),
        tags: ['Gym Orders'],
        parameters: [
            new OA\Parameter(name: 'code', description: 'Mã đơn', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'GYM-7K2M9QXA1B')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã ký', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymOrder'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc đơn đã ký'),
        ]
    )]
    public function acceptTerms(Request $request, Order $order): JsonResponse
    {
        $this->ensureOwner($order);

        $terms = $request->validate([
            'terms_accepted' => 'required|accepted',
            'signature_data' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:'.self::SIGNATURE_MAX_LENGTH],
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => 'required|string|max:20',
            'buyer_email' => 'required|email|max:255',
        ]);

        return ResponseAPI::success($this->mapOrder($this->orderService->acceptTerms($order, $terms)));
    }

    #[OA\Post(
        path: '/payments',
        description: 'Thanh toán đơn đã ký. **Hiện là mock**: thành công ngay và cấp gói. Gọi lại trên đơn đã thanh toán không cấp gói lần hai.',
        summary: 'Thanh toán đơn hàng (mock)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['order_code', 'payment_method'],
            properties: [
                new OA\Property(property: 'order_code', type: 'string', example: 'GYM-7K2M9QXA1B'),
                new OA\Property(property: 'payment_method', type: 'string', enum: ['momo', 'vnpay', 'cash'], example: 'momo'),
            ]
        )),
        tags: ['Gym Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Đã thanh toán', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymOrder'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Chưa ký điều khoản, dữ liệu không hợp lệ, hoặc mock đang tắt'),
        ]
    )]
    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_code' => 'required|string',
            'payment_method' => ['required', Rule::in(PaymentMethod::getValues())],
        ]);

        $order = Order::query()->where('code', $validated['order_code'])->firstOrFail();
        $this->ensureOwner($order);

        return ResponseAPI::success($this->mapOrder(
            $this->orderService->payWithMock($order, $validated['payment_method'])
        ));
    }

    private function ensureOwner(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 404);
    }
}
