<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Http\Controllers\Concerns\HandlesPresignedFileUpload;
use App\Share\Http\Requests\PresignedFileUploadRequest;
use App\Share\Services\File\FileUploadService;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class FileController extends BaseAPIController
{
    use HandlesPresignedFileUpload;

    /**
     * Lấy presigned URL để upload file lên S3
     */
    #[OA\Post(
        path: '/files/presigned-upload',
        description: 'Tạo presigned PUT URL để client upload trực tiếp lên S3. Hỗ trợ mọi FileType trong config/app_file.php. Validate mimetype và max size theo config.',
        summary: 'Presigned upload file',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'filename', 'mimetype', 'size'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['banner_image', 'program_cover', 'lesson_video', 'lesson_thumbnail'], example: 'program_cover'),
                    new OA\Property(property: 'filename', type: 'string', example: 'cover.jpg'),
                    new OA\Property(property: 'mimetype', type: 'string', example: 'image/jpeg'),
                    new OA\Property(property: 'size', description: 'Kích thước file (bytes)', type: 'integer', example: 204800),
                ]
            )
        ),
        tags: ['Files'],
        responses: [
            new OA\Response(response: 200, description: 'Tạo presigned URL thành công'),
            new OA\Response(response: 401, description: 'Chưa xác thực'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function presignedUpload(PresignedFileUploadRequest $request, FileUploadService $fileUploadService): JsonResponse
    {
        return $this->presignedFileUpload($request, $fileUploadService);
    }
}
