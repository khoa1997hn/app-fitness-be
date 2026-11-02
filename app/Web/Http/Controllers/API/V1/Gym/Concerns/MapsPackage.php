<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Concerns;

use App\Share\Models\Package;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymPackage',
    required: ['id', 'name', 'package_type', 'price', 'duration_days', 'session_duration_minutes', 'is_off_peak', 'base_people_max', 'extra_people_session_max', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'name', type: 'string', example: '10 classes'),
        new OA\Property(property: 'description', type: 'string', example: '4 tháng + 15 ngày.', nullable: true),
        new OA\Property(property: 'package_type', type: 'string', enum: ['drop_in', 'class_pack', 'unlimited', 'intro_demo', 'private_pack', 'private_group'], example: 'class_pack'),
        new OA\Property(property: 'price', description: 'Giá (VNĐ)', type: 'integer', example: 2100000),
        new OA\Property(property: 'duration_days', description: 'Thời hạn (ngày)', type: 'integer', example: 135),
        new OA\Property(property: 'class_credits', description: 'Số buổi, null = không giới hạn', type: 'integer', example: 10, nullable: true),
        new OA\Property(property: 'session_duration_minutes', description: 'Thời lượng mỗi buổi (phút)', type: 'integer', example: 60),
        new OA\Property(property: 'is_off_peak', type: 'boolean', example: false),
        new OA\Property(property: 'base_people_max', description: 'Số người cơ bản mỗi buổi (gói Private)', type: 'integer', example: 2),
        new OA\Property(property: 'extra_people_session_max', description: 'Trần người thêm mỗi buổi (gói Private)', type: 'integer', example: 100),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'hidden'], example: 'active'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
trait MapsPackage
{
    /**
     * @return array<string, mixed>
     */
    private function mapPackage(Package $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'description' => $package->description,
            'package_type' => $package->package_type,
            'price' => $package->price,
            'duration_days' => $package->duration_days,
            'class_credits' => $package->class_credits,
            'session_duration_minutes' => $package->session_duration_minutes,
            'is_off_peak' => $package->is_off_peak,
            'base_people_max' => $package->base_people_max,
            'extra_people_session_max' => $package->extra_people_session_max,
            'status' => $package->status,
            'created_at' => $package->created_at,
            'updated_at' => $package->updated_at,
        ];
    }
}
