<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Enums\PackageStatus;
use App\Share\Models\Package;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use App\Web\Http\Controllers\API\V1\Gym\Concerns\MapsPackage;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PackageController extends BaseAPIController
{
    use MapsPackage;

    #[OA\Get(
        path: '/packages',
        description: 'Danh sách gói tập phòng tập đang mở bán. Công khai, không cần đăng nhập.',
        summary: 'Danh sách gói tập',
        tags: ['Gym Packages'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymPackage')),
                    ]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $packages = Package::query()
            ->withTranslation()
            ->where('status', PackageStatus::Active)
            ->orderBy('id')
            ->get();

        return ResponseAPI::success(
            $packages->map(fn (Package $package) => $this->mapPackage($package))->all()
        );
    }

    #[OA\Get(
        path: '/packages/{package}',
        description: 'Chi tiết gói tập đang mở bán. Gói đã ẩn trả 404. Công khai.',
        summary: 'Chi tiết gói tập',
        tags: ['Gym Packages'],
        parameters: [
            new OA\Parameter(name: 'package', description: 'ID gói', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 3)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Thành công',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/GymPackage'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Không tìm thấy hoặc gói đã ẩn'),
        ]
    )]
    public function show(Package $package): JsonResponse
    {
        abort_unless($package->status->is(PackageStatus::Active), 404);

        return ResponseAPI::success($this->mapPackage($package));
    }
}
