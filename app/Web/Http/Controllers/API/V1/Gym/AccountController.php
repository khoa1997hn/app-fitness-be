<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Enums\Gender;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class AccountController extends BaseAPIController
{
    #[OA\Patch(
        path: '/users/me',
        description: 'Tài khoản đã đăng nhập (mọi role) sửa thông tin cá nhân. Không sửa được email, role.',
        summary: 'Cập nhật thông tin cá nhân',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['first_name', 'last_name', 'phone'], properties: [
            new OA\Property(property: 'first_name', type: 'string', example: 'Phạm Thị'),
            new OA\Property(property: 'last_name', type: 'string', example: 'Mai'),
            new OA\Property(property: 'phone', type: 'string', example: '0900000004'),
            new OA\Property(property: 'dob', type: 'string', format: 'date', nullable: true),
            new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female', 'other'], nullable: true),
        ])),
        tags: ['Gym Account'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'dob' => 'nullable|date_format:Y-m-d',
            'gender' => ['nullable', Rule::in(Gender::getValues())],
        ]);
        /** @var User $user */
        $user = auth()->user();
        $user->update($validated);

        return ResponseAPI::success([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'dob' => $user->dob?->toDateString(),
            'gender' => $user->gender,
        ]);
    }

    #[OA\Patch(
        path: '/users/me/password',
        description: 'Đổi mật khẩu, dùng cho mọi tài khoản đã đăng nhập.',
        summary: 'Đổi mật khẩu',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['current_password', 'password', 'password_confirmation'], properties: [
            new OA\Property(property: 'current_password', type: 'string'),
            new OA\Property(property: 'password', type: 'string', minLength: 8, maxLength: 50),
            new OA\Property(property: 'password_confirmation', type: 'string'),
        ])),
        tags: ['Gym Account'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công'),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 422, description: 'Sai mật khẩu hiện tại hoặc dữ liệu không hợp lệ'),
        ]
    )]
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|max:50|confirmed',
        ]);
        /** @var User $user */
        $user = auth()->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw new GymException(__('messages.gym_password_incorrect'));
        }

        $user->update(['password' => $validated['password']]);

        return ResponseAPI::success();
    }
}
