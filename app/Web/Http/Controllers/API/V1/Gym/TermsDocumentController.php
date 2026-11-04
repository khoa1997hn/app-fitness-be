<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Services\Gym\TermsDocumentService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TermsDocumentController extends BaseAPIController
{
    public function __construct(private readonly TermsDocumentService $termsDocumentService) {}

    #[OA\Get(
        path: '/api/v1/terms-document',
        description: 'Vị trí ô chữ ký trên file điều khoản, tính theo tỉ lệ trang (0 đến 1), gốc ở góc trên bên trái. Công khai.',
        summary: 'Thông tin ô chữ ký trên file điều khoản',
        tags: ['Gym Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'x', type: 'number', example: 0.55),
                    new OA\Property(property: 'y', type: 'number', example: 0.72),
                    new OA\Property(property: 'width', type: 'number', example: 0.35),
                    new OA\Property(property: 'height', type: 'number', example: 0.13),
                ], type: 'object'),
            ])),
        ]
    )]
    public function show(): JsonResponse
    {
        return ResponseAPI::success($this->termsDocumentService->signaturePlacement());
    }

    #[OA\Get(
        path: '/api/v1/terms-document/file',
        description: 'File PDF điều khoản chưa ký. Công khai.',
        summary: 'Tải file điều khoản',
        tags: ['Gym Orders'],
        responses: [
            new OA\Response(response: 200, description: 'File PDF', content: new OA\MediaType(mediaType: 'application/pdf')),
        ]
    )]
    public function file(): BinaryFileResponse
    {
        return response()->file($this->termsDocumentService->path(), ['Content-Type' => 'application/pdf']);
    }
}
