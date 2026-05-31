<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class ProgramFavoriteController extends BaseAPIController
{
    /**
     * Yêu thích một hoặc nhiều program (idempotent, không ghi đè favorite cũ)
     */
    #[OA\Post(
        path: '/programs/favorites',
        description: 'Đánh dấu yêu thích một hoặc nhiều program cho user hiện tại. User có thể yêu thích nhiều program; mỗi lần gọi chỉ thêm các program trong body, không xóa các program đã yêu thích trước đó. Idempotent: gọi lại với cùng program_ids vẫn trả 200, không tạo bản ghi trùng.',
        summary: 'Yêu thích program',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['program_ids'],
                properties: [
                    new OA\Property(
                        property: 'program_ids',
                        description: 'Danh sách ID program cần yêu thích (unique)',
                        type: 'array',
                        items: new OA\Items(type: 'integer', example: 1),
                        example: [1, 2]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Đánh dấu yêu thích thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'object', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ hoặc program không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validated = $request->validate([
            'program_ids' => ['required', 'array', 'min:1'],
            'program_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);

        $programIds = array_values(array_unique($validated['program_ids']));

        $existingCount = Program::query()->whereIn('id', $programIds)->count();
        if ($existingCount !== count($programIds)) {
            throw ValidationException::withMessages([
                'program_ids' => [__('messages.program_not_found')],
            ]);
        }

        $user->favoritePrograms()->syncWithoutDetaching($programIds);

        return ResponseAPI::success();
    }
}
