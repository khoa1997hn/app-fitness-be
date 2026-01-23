<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Http\Controllers\Controller;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Lấy thông tin user đang đăng nhập
     */
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return ResponseAPI::success([
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'dob' => $user->dob,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }
}
