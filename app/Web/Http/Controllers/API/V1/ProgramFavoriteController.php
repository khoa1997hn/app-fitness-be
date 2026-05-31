<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProgramFavoriteController extends BaseAPIController
{
    /**
     * Yêu thích một program (idempotent)
     */
    #[OA\Post(
        path: '/programs/{program}/favorite',
        description: 'Đánh dấu yêu thích một program cho user hiện tại. Idempotent: gọi lại khi đã yêu thích vẫn trả 200, không tạo bản ghi trùng.',
        summary: 'Yêu thích program',
        security: [['bearerAuth' => []]],
        tags: ['Program Favorites'],
        parameters: [
            new OA\Parameter(name: 'program', description: 'ID program', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
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
            new OA\Response(response: 404, description: 'Program không tồn tại'),
            new OA\Response(response: 500, description: 'Lỗi server'),
        ]
    )]
    public function store(Program $program): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $user->favoritePrograms()->syncWithoutDetaching([$program->id]);

        return ResponseAPI::success();
    }
}
