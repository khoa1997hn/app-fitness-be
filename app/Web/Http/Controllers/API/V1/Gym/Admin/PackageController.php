<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\PackageStatus;
use App\Share\Enums\PackageType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\Package;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymPackageInput',
    required: ['name', 'package_type', 'price', 'duration_days', 'session_duration_minutes'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: '10 classes'),
        new OA\Property(property: 'description', type: 'string', example: '4 tháng + 15 ngày.', nullable: true),
        new OA\Property(property: 'package_type', type: 'string', enum: ['drop_in', 'class_pack', 'unlimited', 'intro_demo', 'private_pack', 'private_group'], example: 'class_pack'),
        new OA\Property(property: 'price', description: 'Giá (VNĐ), lớn hơn 0', type: 'integer', example: 2100000),
        new OA\Property(property: 'duration_days', type: 'integer', example: 135),
        new OA\Property(property: 'class_credits', description: 'Bắt buộc trừ gói unlimited; gói unlimited luôn lưu null', type: 'integer', example: 10, nullable: true),
        new OA\Property(property: 'session_duration_minutes', type: 'integer', example: 60),
        new OA\Property(property: 'is_off_peak', type: 'boolean', example: false),
        new OA\Property(property: 'base_people_max', type: 'integer', example: 2),
        new OA\Property(property: 'extra_people_session_max', type: 'integer', example: 100),
    ]
)]
#[OA\Schema(
    schema: 'GymAdminPackage',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymPackage'),
        new OA\Schema(properties: [
            new OA\Property(property: 'can_delete', description: 'Gói đã có đơn hàng thì chỉ được ẩn', type: 'boolean', example: true),
        ]),
    ]
)]
class PackageController extends BaseAPIController
{
    use MapsPackage;

    #[OA\Get(
        path: '/admin/packages',
        description: 'Danh sách gói tập, mới nhất trước. Quyền `packages.manage`.',
        summary: 'Admin: danh sách gói tập',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Packages'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Tìm theo tên gói', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', description: 'Lọc trạng thái', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'hidden'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymAdminPackage')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => ['nullable', Rule::in(PackageStatus::getValues())],
        ]);

        $packages = Package::query()
            ->withTranslation()
            ->withExists('orders')
            ->when($validated['q'] ?? null, fn ($query, string $q) => $query->whereTranslationLike('name', "%{$q}%"))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success(
            $packages->map(fn (Package $package) => $this->mapAdminPackage($package))->all()
        );
    }

    #[OA\Get(
        path: '/admin/packages/{package}',
        description: 'Chi tiết gói tập, gồm cả gói đã ẩn. Quyền `packages.manage`.',
        summary: 'Admin: chi tiết gói tập',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Packages'],
        parameters: [
            new OA\Parameter(name: 'package', description: 'ID gói', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminPackage'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(Package $package): JsonResponse
    {
        return ResponseAPI::success($this->mapAdminPackage($package->loadExists('orders')));
    }

    #[OA\Post(
        path: '/admin/packages',
        description: 'Tạo gói tập. Tên và mô tả lưu theo locale của header `x-locale`. Quyền `packages.manage`.',
        summary: 'Admin: tạo gói tập',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GymPackageInput')),
        tags: ['Gym Admin Packages'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Đã tạo',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminPackage'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $package = Package::query()->create($this->validatePackage($request));

        return ResponseAPI::success($this->mapAdminPackage($package->refresh()->loadExists('orders')), code: 201);
    }

    #[OA\Patch(
        path: '/admin/packages/{package}',
        description: 'Sửa gói tập. Đổi giá không ảnh hưởng gói học viên đã mua. Quyền `packages.manage`.',
        summary: 'Admin: sửa gói tập',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/GymPackageInput')),
        tags: ['Gym Admin Packages'],
        parameters: [
            new OA\Parameter(name: 'package', description: 'ID gói', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đã cập nhật',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminPackage'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(Request $request, Package $package): JsonResponse
    {
        $package->update($this->validatePackage($request));

        return ResponseAPI::success($this->mapAdminPackage($package->refresh()->loadExists('orders')));
    }

    #[OA\Patch(
        path: '/admin/packages/{package}/status',
        description: 'Ẩn hoặc mở bán gói. Gói ẩn không hiện cho học viên mới, người đã mua vẫn dùng tới hết hạn. Quyền `packages.manage`.',
        summary: 'Admin: ẩn/hiện gói tập',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [new OA\Property(property: 'status', type: 'string', enum: ['active', 'hidden'], example: 'hidden')]
            )
        ),
        tags: ['Gym Admin Packages'],
        parameters: [
            new OA\Parameter(name: 'package', description: 'ID gói', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đã cập nhật',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/GymAdminPackage'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function updateStatus(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(PackageStatus::getValues())],
        ]);

        $package->update(['status' => $validated['status']]);

        return ResponseAPI::success($this->mapAdminPackage($package->refresh()->loadExists('orders')));
    }

    #[OA\Delete(
        path: '/admin/packages/{package}',
        description: 'Xóa cứng gói tập. Gói đã có đơn hàng trả 422, hãy dùng Ẩn. Quyền `packages.manage`.',
        summary: 'Admin: xóa gói tập',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Packages'],
        parameters: [
            new OA\Parameter(name: 'package', description: 'ID gói', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đã xóa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'string', example: null, nullable: true),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Gói đã có đơn hàng'),
        ]
    )]
    public function destroy(Package $package): JsonResponse
    {
        if ($package->orders()->exists()) {
            throw new GymException(__('messages.gym_package_has_orders'));
        }

        $package->delete();

        return ResponseAPI::success();
    }

    /**
     * Thêm `can_delete`: gói đã có đơn hàng thì chỉ được ẩn. Cần withExists/loadExists `orders`.
     *
     * @return array<string, mixed>
     */
    private function mapAdminPackage(Package $package): array
    {
        return [
            ...$this->mapPackage($package),
            'can_delete' => ! $package->orders_exists,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePackage(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'package_type' => ['required', Rule::in(PackageType::getValues())],
            'price' => 'required|integer|min:1|max:999999999999',
            'duration_days' => 'required|integer|min:1|max:65535',
            'class_credits' => 'exclude_if:package_type,'.PackageType::Unlimited.'|required|integer|min:1|max:65535',
            'session_duration_minutes' => 'required|integer|min:1|max:65535',
            'is_off_peak' => 'sometimes|boolean',
            'base_people_max' => 'sometimes|integer|min:1|max:65535',
            'extra_people_session_max' => 'sometimes|integer|min:0|max:65535',
        ]);

        $validated['class_credits'] ??= null;

        return $validated;
    }
}
