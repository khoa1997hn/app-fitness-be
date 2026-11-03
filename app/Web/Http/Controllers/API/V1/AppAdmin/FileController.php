<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use App\Share\Http\Controllers\Concerns\HandlesPresignedFileUpload;
use App\Share\Http\Requests\PresignedFileUploadRequest;
use App\Share\Services\File\FileUploadService;
use App\Web\Http\Controllers\API\V1\APIController as BaseController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class FileController extends BaseController
{
    use HandlesPresignedFileUpload;

    #[OA\Post(
        path: '/admin/app/files/presigned-upload',
        summary: 'Tạo URL upload S3',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminUploadInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminUpload')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function presignedUpload(PresignedFileUploadRequest $request, FileUploadService $fileUploadService): JsonResponse
    {
        return $this->presignedFileUpload($request, $fileUploadService);
    }
}
