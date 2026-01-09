<?php

namespace App\Web\Http\Controllers\API\V1\Auth;

use App\Share\Http\Controllers\Controller;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    /**
     * Đăng ký user mới
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:50',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date:Y-m-d',
        ]);

        $user = User::query()->create([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'dob' => $validated['dob'],
        ]);

        return ResponseAPI::success($user, __('messages.registration_success'), 201);
    }
}
