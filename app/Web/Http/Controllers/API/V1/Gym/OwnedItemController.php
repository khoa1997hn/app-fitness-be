<?php

namespace App\Web\Http\Controllers\API\V1\Gym;

use App\Share\Models\UserPackage;
use App\Share\Models\UserTherapy;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class OwnedItemController extends BaseAPIController
{
    #[OA\Get(
        path: '/users/me/packages',
        description: 'Gói tập đã mua, gồm cả gói hết hạn. Hết hạn khi `end_date` đã qua.',
        summary: 'Gói tập của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 7),
                    new OA\Property(property: 'package_id', type: 'integer', example: 3),
                    new OA\Property(property: 'name', type: 'string', example: '10 classes'),
                    new OA\Property(property: 'package_type', type: 'string', enum: ['drop_in', 'class_pack', 'unlimited', 'intro_demo', 'private_pack', 'private_group'], example: 'class_pack'),
                    new OA\Property(property: 'total_credits', description: 'null = không giới hạn', type: 'integer', example: 10, nullable: true),
                    new OA\Property(property: 'remaining_credits', type: 'integer', example: 10, nullable: true),
                    new OA\Property(property: 'session_duration_minutes', type: 'integer', example: 60),
                    new OA\Property(property: 'is_off_peak', type: 'boolean', example: false),
                    new OA\Property(property: 'base_people_max', type: 'integer', example: 2),
                    new OA\Property(property: 'extra_people_session_max', type: 'integer', example: 100),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-11-02'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-03-17'),
                ], type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function packages(): JsonResponse
    {
        $packages = UserPackage::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($packages->map(fn (UserPackage $package) => [
            'id' => $package->id,
            'package_id' => $package->package_id,
            'name' => $package->name,
            'package_type' => $package->package_type,
            'total_credits' => $package->total_credits,
            'remaining_credits' => $package->remaining_credits,
            'session_duration_minutes' => $package->session_duration_minutes,
            'is_off_peak' => $package->is_off_peak,
            'base_people_max' => $package->base_people_max,
            'extra_people_session_max' => $package->extra_people_session_max,
            'start_date' => $package->start_date->toDateString(),
            'end_date' => $package->end_date->toDateString(),
        ])->all());
    }

    #[OA\Get(
        path: '/users/me/therapies',
        description: 'Gói trị liệu đã mua, gồm cả gói hết hạn.',
        summary: 'Gói trị liệu của tôi',
        security: [['bearerAuth' => []]],
        tags: ['Gym Orders'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 2),
                    new OA\Property(property: 'therapy_service_slug', type: 'string', example: 'deep-stretch'),
                    new OA\Property(property: 'name', type: 'string', example: 'Deep Stretch — Combo 5 buổi'),
                    new OA\Property(property: 'variant', type: 'string', enum: ['single', 'combo5'], example: 'combo5'),
                    new OA\Property(property: 'sessions_total', type: 'integer', example: 5),
                    new OA\Property(property: 'sessions_remaining', type: 'integer', example: 5),
                    new OA\Property(property: 'session_duration_minutes', type: 'integer', example: 60),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-01-31'),
                ], type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không phải hội viên'),
        ]
    )]
    public function therapies(): JsonResponse
    {
        $therapies = UserTherapy::query()
            ->with('therapyService')
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($therapies->map(fn (UserTherapy $therapy) => [
            'id' => $therapy->id,
            'therapy_service_slug' => $therapy->therapyService->slug,
            'name' => $therapy->name,
            'variant' => $therapy->variant,
            'sessions_total' => $therapy->sessions_total,
            'sessions_remaining' => $therapy->sessions_remaining,
            'session_duration_minutes' => $therapy->session_duration_minutes,
            'end_date' => $therapy->end_date->toDateString(),
        ])->all());
    }
}
