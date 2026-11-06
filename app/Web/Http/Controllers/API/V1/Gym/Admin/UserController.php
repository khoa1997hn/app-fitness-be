<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\AttendanceStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\Gender;
use App\Share\Enums\Role;
use App\Share\Enums\UserStatus;
use App\Share\Models\Booking;
use App\Share\Models\Order;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use App\Share\Models\UserTherapy;
use App\Share\Services\Auth\ForgotPasswordService;
use App\Share\Services\Gym\AttendanceService;
use App\Share\Services\Gym\UserAccountService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[OA\Schema(
    schema: 'GymUser',
    required: ['id', 'email', 'first_name', 'last_name', 'full_name', 'role', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'registration_source', type: 'string', enum: ['app', 'web', 'admin', 'receptionist', 'trainer'], nullable: true),
        new OA\Property(property: 'created_by', type: 'object', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'full_name', type: 'string'), new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer'])], nullable: true),
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Phạm Thị'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Mai'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Phạm Thị Mai'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], nullable: true),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer', 'member']),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'locked']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'GymUserDetail',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/GymUser'),
        new OA\Schema(properties: [
            new OA\Property(property: 'packages', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymUserPackageItem')),
            new OA\Property(property: 'therapies', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymUserPackageItem')),
            new OA\Property(property: 'orders', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymOrder')),
            new OA\Property(property: 'attendance', properties: [
                new OA\Property(property: 'present_count', type: 'integer', example: 8),
                new OA\Property(property: 'absent_count', type: 'integer', example: 1),
                new OA\Property(property: 'unmarked_count', type: 'integer', example: 2),
                new OA\Property(property: 'absent_recent', description: 'Số buổi vắng trong 5 lần điểm danh gần nhất', type: 'integer', example: 1),
                new OA\Property(property: 'rows', description: '20 buổi gần nhất', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'start_time', type: 'string', example: '2026-11-05T07:00:00'),
                    new OA\Property(property: 'end_time', type: 'string', example: '2026-11-05T08:00:00'),
                    new OA\Property(property: 'class_name', type: 'string', example: 'Yoga Flow'),
                    new OA\Property(property: 'trainer_name', type: 'string', example: 'Nguyễn Thu Hà'),
                    new OA\Property(property: 'package_name', type: 'string', example: '10 classes'),
                    new OA\Property(property: 'attendance_status', type: 'string', enum: ['present', 'absent'], nullable: true),
                ], type: 'object')),
            ], type: 'object'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'GymUserPackageItem',
    required: ['id', 'name', 'remaining', 'total', 'end_date', 'is_expired', 'can_extend'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'name', type: 'string', example: '10 classes'),
        new OA\Property(property: 'remaining', description: 'Null nếu không giới hạn buổi', type: 'integer', nullable: true, example: 4),
        new OA\Property(property: 'total', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
        new OA\Property(property: 'is_expired', type: 'boolean', example: false),
        new OA\Property(property: 'can_extend', description: 'Đã hết hạn và còn buổi', type: 'boolean', example: false),
    ]
)]
class UserController extends BaseAPIController
{
    use MapsOrder;

    private const PER_PAGE = 20;

    private const ATTENDANCE_ROW_LIMIT = 20;

    public function __construct(
        private readonly UserAccountService $userAccountService,
        private readonly AttendanceService $attendanceService,
        private readonly ForgotPasswordService $forgotPasswordService,
    ) {}

    #[OA\Get(
        path: '/admin/users',
        description: 'Danh sách mọi tài khoản, kể cả user app chưa mua gì, mới nhất trước. Quyền `users.manage`.',
        summary: 'Admin: danh sách user',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Users'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Tìm theo tên, email, SĐT', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['admin', 'receptionist', 'trainer', 'member'])),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'locked'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymUser')),
                    new OA\Property(property: 'meta', properties: [
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 3),
                        new OA\Property(property: 'total', type: 'integer', example: 45),
                    ], type: 'object'),
                ], type: 'object'),
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
            'role' => ['nullable', Rule::in(Role::getValues())],
            'status' => ['nullable', Rule::in(UserStatus::getValues())],
            'page' => 'nullable|integer|min:1',
        ]);

        $users = User::query()->with('creator')
            ->when($validated['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->where('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like]));
            })
            ->latest('id')
            ->paginate(self::PER_PAGE);

        return ResponseAPI::success([
            'items' => $users->getCollection()->map(fn (User $user) => $this->mapUser($user))->all(),
            'meta' => [
                'page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/admin/users',
        description: 'Tạo tài khoản. Chỉ admin tạo được nhân viên, lễ tân và HLV chỉ tạo được hội viên. Quyền `users.manage`.',
        summary: 'Admin: tạo user',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['email', 'password', 'first_name', 'last_name', 'phone', 'role'], properties: [
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', minLength: 8, maxLength: 50),
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'phone', type: 'string'),
            new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], nullable: true),
            new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer', 'member']),
        ])),
        tags: ['Gym Admin Users'],
        responses: [
            new OA\Response(response: 201, description: 'Đã tạo', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymUser'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền tạo role này'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'dob' => 'nullable|date_format:Y-m-d',
            'gender' => ['nullable', Rule::in(Gender::getValues())],
            'role' => ['required', Rule::in(Role::getValues())],
        ]);
        /** @var User $actor */
        $actor = auth()->user();

        if ($validated['role'] !== Role::Member && ! $actor->hasPermission('users.change_role')) {
            throw new AccessDeniedHttpException;
        }

        $user = User::query()->create([...$validated, 'registration_source' => $actor->role->value, 'created_by' => $actor->id]);

        return ResponseAPI::success($this->mapUser($user->refresh()), code: 201);
    }

    #[OA\Get(
        path: '/admin/users/{user}',
        description: 'Chi tiết user kèm gói, đơn hàng và điểm danh. Quyền `users.manage`.',
        summary: 'Admin: chi tiết user',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymUserDetail'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(User $user): JsonResponse
    {
        return ResponseAPI::success($this->mapDetail($user));
    }

    #[OA\Patch(
        path: '/admin/users/{user}',
        description: 'Sửa thông tin cá nhân, không sửa email và role. Lễ tân và HLV chỉ sửa được hội viên. Quyền `users.manage`.',
        summary: 'Admin: sửa user',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['first_name', 'last_name', 'phone'], properties: [
            new OA\Property(property: 'first_name', type: 'string'),
            new OA\Property(property: 'last_name', type: 'string'),
            new OA\Property(property: 'phone', type: 'string'),
            new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], nullable: true),
        ])),
        tags: ['Gym Admin Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymUser'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(Request $request, User $user): JsonResponse
    {
        $this->userAccountService->assertCanManage($this->actor(), $user);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'dob' => 'nullable|date_format:Y-m-d',
            'gender' => ['nullable', Rule::in(Gender::getValues())],
        ]);

        $user->update($validated);

        return ResponseAPI::success($this->mapUser($user));
    }

    #[OA\Patch(
        path: '/admin/users/{user}/status',
        description: 'Khóa hoặc mở khóa. Khóa có hiệu lực ngay với cả token còn hạn. Không tự khóa được mình. Lễ tân và HLV chỉ thao tác trên hội viên. Quyền `users.manage`.',
        summary: 'Admin: khóa, mở khóa user',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['status'], properties: [
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'locked']),
        ])),
        tags: ['Gym Admin Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymUser'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Tự khóa mình hoặc dữ liệu không hợp lệ'),
        ]
    )]
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(UserStatus::getValues())]]);
        $this->userAccountService->assertCanManage($this->actor(), $user);
        $this->userAccountService->assertNotSelf($this->actor(), $user);

        $user->update(['status' => $validated['status']]);

        return ResponseAPI::success($this->mapUser($user));
    }

    #[OA\Patch(
        path: '/admin/users/{user}/role',
        description: 'Đổi role. Không tự đổi role của mình. Quyền `users.change_role`, chỉ admin.',
        summary: 'Admin: đổi role',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['role'], properties: [
            new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer', 'member']),
        ])),
        tags: ['Gym Admin Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymUser'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Tự đổi role hoặc dữ liệu không hợp lệ'),
        ]
    )]
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate(['role' => ['required', Rule::in(Role::getValues())]]);
        $this->userAccountService->assertNotSelf($this->actor(), $user);

        $user->update(['role' => $validated['role']]);

        return ResponseAPI::success($this->mapUser($user));
    }

    #[OA\Post(
        path: '/admin/users/{user}/reset-password',
        description: 'Gửi liên kết đặt lại mật khẩu qua email user. Lễ tân và HLV chỉ thao tác trên hội viên. Quyền `users.manage`.',
        summary: 'Admin: gửi liên kết đặt lại mật khẩu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function resetPassword(User $user): JsonResponse
    {
        $this->userAccountService->assertCanManage($this->actor(), $user);

        $this->forgotPasswordService->sendResetLinkIfUserExists($user->email);

        return ResponseAPI::success(null, __('messages.password_reset_email_sent'));
    }

    #[OA\Delete(
        path: '/admin/users/{user}',
        description: 'Xóa mềm tài khoản chưa có gói, đơn hàng hay lịch dạy. Đã có thì chỉ khóa được. Quyền `records.delete`, chỉ admin.',
        summary: 'Admin: xóa user',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Users'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Tự xóa mình hoặc tài khoản đã có dữ liệu'),
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        $this->userAccountService->assertNotSelf($this->actor(), $user);
        $this->userAccountService->assertDeletable($user);

        $user->delete();

        return ResponseAPI::success();
    }

    #[OA\Patch(
        path: '/admin/users/{user}/packages/{userPackage}/extend',
        description: 'Cộng ngày vào `end_date` của gói đã hết hạn và còn buổi, không thêm buổi. Quyền `users.extend_package`, chỉ admin.',
        summary: 'Admin: gia hạn gói tập',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['extra_days'], properties: [
            new OA\Property(property: 'extra_days', type: 'integer', minimum: 1, maximum: 3650, example: 15),
        ])),
        tags: ['Gym Admin Users'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'userPackage', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymUserPackageItem'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Gói không thuộc user này'),
            new OA\Response(response: 422, description: 'Gói chưa hết hạn hoặc đã hết buổi'),
        ]
    )]
    public function extendPackage(Request $request, User $user, UserPackage $userPackage): JsonResponse
    {
        $validated = $request->validate(['extra_days' => 'required|integer|min:1|max:3650']);
        abort_unless($userPackage->user_id === $user->id, 404);

        return ResponseAPI::success($this->mapPackageItem($this->userAccountService->extendPackage($userPackage, $validated['extra_days'])));
    }

    #[OA\Patch(
        path: '/admin/users/{user}/therapies/{userTherapy}/extend',
        description: 'Cộng ngày vào `end_date` của gói trị liệu đã hết hạn và còn buổi, không thêm buổi. Quyền `users.extend_package`, chỉ admin.',
        summary: 'Admin: gia hạn gói trị liệu',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['extra_days'], properties: [
            new OA\Property(property: 'extra_days', type: 'integer', minimum: 1, maximum: 3650, example: 15),
        ])),
        tags: ['Gym Admin Users'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'userTherapy', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymUserPackageItem'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Gói không thuộc user này'),
            new OA\Response(response: 422, description: 'Gói chưa hết hạn hoặc đã hết buổi'),
        ]
    )]
    public function extendTherapy(Request $request, User $user, UserTherapy $userTherapy): JsonResponse
    {
        $validated = $request->validate(['extra_days' => 'required|integer|min:1|max:3650']);
        abort_unless($userTherapy->user_id === $user->id, 404);

        return ResponseAPI::success($this->mapTherapyItem($this->userAccountService->extendTherapy($userTherapy, $validated['extra_days'])));
    }

    private function actor(): User
    {
        /** @var User $actor */
        $actor = auth()->user();

        return $actor;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapUser(User $user): array
    {
        return [
            ...$user->registrationProvenance(),
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim($user->first_name.' '.$user->last_name),
            'phone' => $user->phone,
            'dob' => $user->dob?->toDateString(),
            'gender' => $user->gender,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => $user->created_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDetail(User $user): array
    {
        return [
            ...$this->mapUser($user),
            'packages' => UserPackage::query()->where('user_id', $user->id)->latest('id')->get()
                ->map(fn (UserPackage $package) => $this->mapPackageItem($package))->all(),
            'therapies' => UserTherapy::query()->where('user_id', $user->id)->latest('id')->get()
                ->map(fn (UserTherapy $therapy) => $this->mapTherapyItem($therapy))->all(),
            'orders' => Order::query()->where('user_id', $user->id)->latest('id')->get()
                ->map(fn (Order $order) => $this->mapOrder($order))->all(),
            'attendance' => $this->mapAttendance($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPackageItem(UserPackage $package): array
    {
        $isExpired = $package->end_date->copy()->endOfDay()->isPast();

        return [
            'id' => $package->id,
            'name' => $package->name,
            'remaining' => $package->remaining_credits,
            'total' => $package->total_credits,
            'end_date' => $package->end_date->toDateString(),
            'is_expired' => $isExpired,
            'can_extend' => $isExpired && ($package->remaining_credits === null || $package->remaining_credits > 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTherapyItem(UserTherapy $therapy): array
    {
        $isExpired = $therapy->end_date->copy()->endOfDay()->isPast();

        return [
            'id' => $therapy->id,
            'name' => $therapy->name,
            'remaining' => $therapy->sessions_remaining,
            'total' => $therapy->sessions_total,
            'end_date' => $therapy->end_date->toDateString(),
            'is_expired' => $isExpired,
            'can_extend' => $isExpired && $therapy->sessions_remaining > 0,
        ];
    }

    /**
     * Chỉ tính buổi đã tới ngày học, không tính lịch tương lai.
     *
     * @return array<string, mixed>
     */
    private function mapAttendance(User $user): array
    {
        $bookings = Booking::query()
            ->with(['classSchedule.fitnessClass', 'classSchedule.trainer', 'userPackage', 'attendanceRecord'])
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::Confirmed)
            ->whereHas('classSchedule', fn ($query) => $query->where('start_time', '<=', now()->endOfDay()))
            ->get()
            ->sortByDesc(fn (Booking $booking) => $booking->classSchedule->start_time)
            ->values();

        $statuses = $bookings->map(fn (Booking $booking) => $booking->attendanceRecord?->status?->value);

        return [
            'present_count' => $statuses->filter(fn (?string $status) => $status === AttendanceStatus::Present)->count(),
            'absent_count' => $statuses->filter(fn (?string $status) => $status === AttendanceStatus::Absent)->count(),
            'unmarked_count' => $statuses->filter(fn (?string $status) => $status === null)->count(),
            'absent_recent' => $this->attendanceService->recentAbsences(collect([$user->id]))[$user->id] ?? 0,
            'rows' => $bookings->take(self::ATTENDANCE_ROW_LIMIT)->map(fn (Booking $booking) => [
                'start_time' => $booking->classSchedule->start_time->format('Y-m-d\TH:i:s'),
                'end_time' => $booking->classSchedule->end_time->format('Y-m-d\TH:i:s'),
                'class_name' => $booking->classSchedule->fitnessClass->name,
                'trainer_name' => trim($booking->classSchedule->trainer->first_name.' '.$booking->classSchedule->trainer->last_name),
                'package_name' => $booking->userPackage->name,
                'attendance_status' => $booking->attendanceRecord?->status,
            ])->all(),
        ];
    }
}
