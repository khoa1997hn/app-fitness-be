<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Models\TherapyService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymTherapyService',
    required: ['id', 'slug', 'name', 'single_price', 'single_duration_minutes', 'combo5_price', 'combo5_sessions', 'benefits'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'slug', type: 'string', example: 'deep-stretch'),
        new OA\Property(property: 'name', type: 'string', example: 'Deep Stretch'),
        new OA\Property(property: 'long_description', type: 'string', nullable: true),
        new OA\Property(property: 'benefits', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'single_price', type: 'integer', example: 500000),
        new OA\Property(property: 'single_duration_minutes', type: 'integer', example: 60),
        new OA\Property(property: 'combo5_price', type: 'integer', example: 2000000),
        new OA\Property(property: 'combo5_sessions', type: 'integer', example: 5),
    ]
)]
class TherapyServiceController extends BaseAPIController
{
    #[OA\Get(
        path: '/therapy-services',
        description: 'Danh sách dịch vụ trị liệu Recovery. Công khai.',
        summary: 'Danh sách dịch vụ trị liệu',
        tags: ['Gym Packages'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymTherapyService')),
            ])),
        ]
    )]
    public function index(): JsonResponse
    {
        $services = TherapyService::query()->withTranslation()->orderBy('id')->get();

        return ResponseAPI::success($services->map(fn (TherapyService $service) => $this->mapService($service))->all());
    }

    #[OA\Get(
        path: '/therapy-services/{slug}',
        description: 'Chi tiết dịch vụ trị liệu. Công khai.',
        summary: 'Chi tiết dịch vụ trị liệu',
        tags: ['Gym Packages'],
        parameters: [
            new OA\Parameter(name: 'slug', description: 'Slug dịch vụ', in: 'path', required: true, schema: new OA\Schema(type: 'string', example: 'deep-stretch')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', ref: '#/components/schemas/GymTherapyService'),
            ])),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function show(TherapyService $therapyService): JsonResponse
    {
        return ResponseAPI::success($this->mapService($therapyService));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapService(TherapyService $service): array
    {
        return [
            'id' => $service->id,
            'slug' => $service->slug,
            'name' => $service->name,
            'long_description' => $service->long_description,
            'benefits' => $service->benefits ?? [],
            'single_price' => $service->single_price,
            'single_duration_minutes' => $service->single_duration_minutes,
            'combo5_price' => $service->combo5_price,
            'combo5_sessions' => TherapyService::COMBO5_SESSIONS,
        ];
    }
}
