<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Concerns;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Models\PrivateRequest;
use App\Share\Models\TherapyRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymPrivateRequest',
    required: ['id', 'user_package_id', 'package_name', 'discipline', 'requested_at', 'people_count', 'extra_people_count', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 5),
        new OA\Property(property: 'user_package_id', type: 'integer', example: 7),
        new OA\Property(property: 'package_name', type: 'string', example: 'Private 10 buổi'),
        new OA\Property(property: 'package_type', type: 'string', enum: ['private_pack', 'private_group'], example: 'private_pack'),
        new OA\Property(property: 'discipline', type: 'string', enum: ['Yoga', 'Pilates', 'Barre'], example: 'Yoga'),
        new OA\Property(property: 'requested_at', description: 'Giờ địa phương', type: 'string', example: '2026-11-20T15:00:00'),
        new OA\Property(property: 'duration_minutes', type: 'integer', example: 60),
        new OA\Property(property: 'people_count', type: 'integer', example: 2),
        new OA\Property(property: 'extra_people_count', type: 'integer', example: 1),
        new OA\Property(property: 'trainer_id', type: 'integer', nullable: true),
        new OA\Property(property: 'trainer_name', type: 'string', nullable: true),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'], example: 'pending'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'GymTherapyRequest',
    required: ['id', 'user_therapy_id', 'therapy_name', 'preferred_at', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 6),
        new OA\Property(property: 'user_therapy_id', type: 'integer', example: 2),
        new OA\Property(property: 'therapy_name', type: 'string', example: 'Deep Stretch — Combo 5 buổi'),
        new OA\Property(property: 'duration_minutes', type: 'integer', example: 60),
        new OA\Property(property: 'preferred_at', description: 'Giờ địa phương', type: 'string', example: '2026-11-20T15:00:00'),
        new OA\Property(property: 'note', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'rejected', 'cancelled'], example: 'pending'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
trait MapsBookingRequest
{
    /**
     * Cần load `userPackage`, `trainer`.
     *
     * @return array<string, mixed>
     */
    private function mapPrivateRequest(PrivateRequest $request): array
    {
        return [
            'id' => $request->id,
            'user_package_id' => $request->user_package_id,
            'package_name' => $request->userPackage->name,
            'package_type' => $request->userPackage->package_type,
            'discipline' => $request->discipline,
            'requested_at' => $request->requested_at->format('Y-m-d\TH:i:s'),
            'duration_minutes' => $request->userPackage->session_duration_minutes,
            'people_count' => $request->people_count,
            'extra_people_count' => $request->extra_people_count,
            'trainer_id' => $request->trainer_id,
            'trainer_name' => $request->trainer ? trim($request->trainer->first_name.' '.$request->trainer->last_name) : null,
            'note' => $request->note,
            'status' => $request->status,
            'created_at' => $request->created_at,
        ];
    }

    /**
     * Cần load `userTherapy`.
     *
     * @return array<string, mixed>
     */
    private function mapTherapyRequest(TherapyRequest $request): array
    {
        return [
            'id' => $request->id,
            'user_therapy_id' => $request->user_therapy_id,
            'therapy_name' => $request->userTherapy->name,
            'duration_minutes' => $request->userTherapy->session_duration_minutes,
            'preferred_at' => $request->preferred_at->format('Y-m-d\TH:i:s'),
            'note' => $request->note,
            'status' => $request->status,
            'created_at' => $request->created_at,
        ];
    }

    /**
     * Trạng thái hiện cho hội viên. Yêu cầu đã hủy không hiện.
     *
     * @return list<string>
     */
    private function memberVisibleStatuses(): array
    {
        return [BookingRequestStatus::Pending, BookingRequestStatus::Confirmed, BookingRequestStatus::Rejected, BookingRequestStatus::Completed];
    }
}
