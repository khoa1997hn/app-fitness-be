<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Concerns;

use App\Share\Models\ClassSchedule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymClassSchedule',
    required: ['id', 'class', 'trainer_id', 'trainer_name', 'room', 'start_time', 'end_time', 'max_slot', 'booked_count', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 15),
        new OA\Property(property: 'class', required: ['id', 'slug', 'name', 'category'], properties: [
            new OA\Property(property: 'id', type: 'integer', example: 8),
            new OA\Property(property: 'slug', type: 'string', example: 'yoga-flow'),
            new OA\Property(property: 'name', type: 'string', example: 'Yoga Flow'),
            new OA\Property(property: 'category', type: 'string', enum: ['sculpt', 'core-pilates', 'yoga-sound', 'barre', 'meditation-sound', 'dance-cardio'], example: 'yoga-sound'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
        ], type: 'object'),
        new OA\Property(property: 'trainer_id', type: 'integer', example: 3),
        new OA\Property(property: 'trainer_name', type: 'string', example: 'Nguyễn Thu Hà'),
        new OA\Property(property: 'room', type: 'string', example: 'P101'),
        new OA\Property(property: 'start_time', description: 'Giờ địa phương Asia/Ho_Chi_Minh', type: 'string', example: '2026-11-05T07:00:00'),
        new OA\Property(property: 'end_time', type: 'string', example: '2026-11-05T08:00:00'),
        new OA\Property(property: 'max_slot', type: 'integer', example: 15),
        new OA\Property(property: 'booked_count', type: 'integer', example: 5),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'cancelled'], example: 'active'),
    ]
)]
trait MapsSchedule
{
    /**
     * Cần load `fitnessClass`, `trainer` và withCount `confirmedBookings`.
     *
     * @return array<string, mixed>
     */
    private function mapSchedule(ClassSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'class' => [
                'id' => $schedule->fitnessClass->id,
                'slug' => $schedule->fitnessClass->slug,
                'name' => $schedule->fitnessClass->name,
                'category' => $schedule->fitnessClass->category,
                'description' => $schedule->fitnessClass->description,
            ],
            'trainer_id' => $schedule->trainer_id,
            'trainer_name' => trim($schedule->trainer->first_name.' '.$schedule->trainer->last_name),
            'room' => $schedule->room,
            'start_time' => $schedule->start_time->format('Y-m-d\TH:i:s'),
            'end_time' => $schedule->end_time->format('Y-m-d\TH:i:s'),
            'max_slot' => $schedule->max_slot,
            'booked_count' => $schedule->confirmed_bookings_count,
            'status' => $schedule->status,
        ];
    }
}
