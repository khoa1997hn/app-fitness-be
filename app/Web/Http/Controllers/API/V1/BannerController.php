<?php

namespace App\Web\Http\Controllers\API\V1;

use App\Share\Models\Banner;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BannerController extends BaseAPIController
{
    /**
     * Lấy danh sách banners
     */
    #[OA\Get(
        path: '/api/v1/banners',
        description: 'Lấy danh sách banners hiển thị trên home page. Có thể filter theo id (1 hoặc nhiều ids), sắp xếp theo order tăng dần và id giảm dần.',
        summary: 'Lấy danh sách banners',
        tags: ['Banners'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Filter theo ID (có thể là 1 ID hoặc mảng nhiều IDs, ví dụ: 1 hoặc 1,2,3)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: '1,2,3')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lấy danh sách banners thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', description: 'ID của banner', type: 'integer', example: 1),
                                    new OA\Property(property: 'image_url', description: 'URL ảnh banner', type: 'string', example: 'https://example.com/banner.jpg'),
                                    new OA\Property(property: 'link_url', description: 'URL khi click vào banner', type: 'string', example: 'https://example.com/page', nullable: true),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Banner::query()->where('is_active', true);

        // Filter theo id (có thể là 1 hoặc nhiều ids)
        if ($idParam = $request->input('id')) {
            $ids = is_array($idParam) ? $idParam : explode(',', $idParam);
            $ids = array_map('intval', array_filter($ids));
            if (! empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        // Sort: order tăng dần, id giảm dần
        $query->orderBy('order', 'asc')->orderBy('id', 'desc');

        $banners = $query->get();

        return ResponseAPI::success(
            $banners->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'image_url' => $banner->image_url,
                    'link_url' => $banner->link_url,
                ];
            })->toArray()
        );
    }
}
