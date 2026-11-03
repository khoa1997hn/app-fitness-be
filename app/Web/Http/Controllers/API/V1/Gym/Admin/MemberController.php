<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\Role;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymMember',
    required: ['id', 'email', 'first_name', 'last_name', 'full_name'],
    properties: [
        new OA\Property(property: 'registration_source', type: 'string', enum: ['app', 'web', 'admin', 'receptionist', 'trainer'], nullable: true),
        new OA\Property(property: 'created_by', type: 'object', properties: [new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'full_name', type: 'string'), new OA\Property(property: 'role', type: 'string', enum: ['admin', 'receptionist', 'trainer'])], nullable: true),
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(property: 'email', type: 'string', example: 'member@demo.kore.vn'),
        new OA\Property(property: 'first_name', type: 'string', example: 'Phạm Thị'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Member'),
        new OA\Property(property: 'full_name', type: 'string', example: 'Phạm Thị Member'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
        new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
    ]
)]
class MemberController extends BaseAPIController
{
    private const SEARCH_LIMIT = 20;

    #[OA\Get(
        path: '/admin/members',
        description: 'Tìm hội viên theo tên, email, SĐT để tạo đơn hộ. Tối đa 20 kết quả. Quyền `users.manage`.',
        summary: 'Admin: tìm hội viên',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Orders'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Từ khóa', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: 'member')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymMember')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['q' => 'required|string|max:255']);
        $like = '%'.$validated['q'].'%';

        $members = User::query()->with('creator')
            ->where('role', Role::Member)
            ->where(fn ($query) => $query
                ->where('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like]))
            ->orderBy('first_name')
            ->limit(self::SEARCH_LIMIT)
            ->get();

        return ResponseAPI::success($members->map(fn (User $member) => $this->mapMember($member))->all());
    }

    #[OA\Get(
        path: '/admin/members/{user}',
        description: 'Thông tin hội viên. Tài khoản nhân viên trả 404. Quyền `users.manage`.',
        summary: 'Admin: chi tiết hội viên',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Orders'],
        parameters: [
            new OA\Parameter(name: 'user', description: 'ID hội viên', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 4)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymMember'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(User $user): JsonResponse
    {
        abort_unless($user->role->is(Role::Member), 404);

        return ResponseAPI::success($this->mapMember($user));
    }

    #[OA\Post(
        path: '/admin/members',
        description: 'Nhân viên tạo tài khoản hộ khách, luôn có role `member`. Nhân viên báo mật khẩu cho khách để đăng nhập ký và thanh toán. Quyền `users.manage`.',
        summary: 'Admin: tạo tài khoản hội viên',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password', 'first_name', 'last_name', 'phone'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'khach@example.com'),
                new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'matkhau123'),
                new OA\Property(property: 'first_name', type: 'string', example: 'Nguyễn Văn'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Khách'),
                new OA\Property(property: 'phone', type: 'string', example: '0900000009'),
                new OA\Property(property: 'dob', type: 'string', format: 'date', example: '1995-01-30', nullable: true),
            ]
        )),
        tags: ['Gym Admin Orders'],
        responses: [
            new OA\Response(response: 201, description: 'Đã tạo', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymMember'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc email đã tồn tại'),
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
            'dob' => 'nullable|date:Y-m-d',
        ]);

        $member = User::query()->create([...$validated, 'role' => Role::Member, 'registration_source' => auth()->user()->role->value, 'created_by' => auth()->id()]);

        return ResponseAPI::success($this->mapMember($member), code: 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapMember(User $member): array
    {
        return [
            ...$member->registrationProvenance(),
            'id' => $member->id,
            'email' => $member->email,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'full_name' => trim($member->first_name.' '.$member->last_name),
            'phone' => $member->phone,
            'dob' => $member->dob?->toDateString(),
        ];
    }
}
