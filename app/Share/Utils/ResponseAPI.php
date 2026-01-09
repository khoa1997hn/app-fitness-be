<?php

namespace App\Share\Utils;

use Illuminate\Http\JsonResponse;

class ResponseAPI
{
    /**
     * Trả về response thành công
     */
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Trả về response lỗi
     */
    public static function error(string $message, ?array $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
