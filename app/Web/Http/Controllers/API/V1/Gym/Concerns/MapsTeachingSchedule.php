<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Concerns;

use App\Share\Models\ClassSchedule;
use App\Share\Models\PrivateRequest;
use App\Share\Models\ScheduleCancelRequest;
use App\Share\Models\User;
use App\Share\Services\Gym\TeachingScheduleService;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymTeachingSchedule',
    required: ['type', 'id', 'title', 'start_time', 'end_time', 'student_count', 'status', 'has_pending_cancel_request'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['class', 'private'], example: 'class'),
        new OA\Property(property: 'id', description: 'ID lớp hoặc ID yêu cầu Private', type: 'integer', example: 15),
        new OA\Property(property: 'title', description: 'Tên lớp hoặc bộ môn Private', type: 'string', example: 'Yoga Flow'),
        new OA\Property(property: 'trainer_id', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'trainer_name', type: 'string', nullable: true, example: 'Nguyễn Thu Hà'),
        new OA\Property(property: 'room', type: 'string', nullable: true, example: 'P101'),
        new OA\Property(property: 'start_time', description: 'Giờ địa phương', type: 'string', example: '2026-11-05T07:00:00'),
        new OA\Property(property: 'end_time', type: 'string', example: '2026-11-05T08:00:00'),
        new OA\Property(property: 'student_count', description: 'Số học viên đã đặt, hoặc số người của buổi Private', type: 'integer', example: 5),
        new OA\Property(property: 'max_slot', type: 'integer', nullable: true, example: 15),
        new OA\Property(property: 'status', type: 'string', enum: ['confirmed', 'completed', 'cancelled'], example: 'confirmed'),
        new OA\Property(property: 'has_pending_cancel_request', type: 'boolean', example: false),
    ]
)]
#[OA\Schema(
    schema: 'GymScheduleCancelRequest',
    required: ['id', 'schedule', 'trainer_name', 'reason', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'schedule', ref: '#/components/schemas/GymTeachingSchedule'),
        new OA\Property(property: 'trainer_name', type: 'string', example: 'Nguyễn Thu Hà'),
        new OA\Property(property: 'reason', type: 'string', example: 'Bị ốm'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected'], example: 'pending'),
        new OA\Property(property: 'review_note', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
trait MapsTeachingSchedule
{
    private function trainerName(?User $trainer): ?string
    {
        return $trainer ? trim($trainer->first_name.' '.$trainer->last_name) : null;
    }

    /**
     * Cần load `fitnessClass`, `trainer`, withCount `confirmedBookings`, withExists `pendingCancelRequests`.
     *
     * @return array<string, mixed>
     */
    private function mapTeachingClass(ClassSchedule $schedule, TeachingScheduleService $service): array
    {
        return [
            'type' => TeachingScheduleService::TypeClass,
            'id' => $schedule->id,
            'title' => $schedule->fitnessClass->name,
            'trainer_id' => $schedule->trainer_id,
            'trainer_name' => $this->trainerName($schedule->trainer),
            'room' => $schedule->room,
            'start_time' => $schedule->start_time->format('Y-m-d\TH:i:s'),
            'end_time' => $schedule->end_time->format('Y-m-d\TH:i:s'),
            'student_count' => $schedule->confirmed_bookings_count,
            'max_slot' => $schedule->max_slot,
            'status' => $service->status($schedule),
            'has_pending_cancel_request' => (bool) $schedule->pending_cancel_requests_exists,
        ];
    }

    /**
     * Cần load `userPackage`, `trainer`, withExists `pendingCancelRequests`.
     *
     * @return array<string, mixed>
     */
    private function mapTeachingPrivate(PrivateRequest $request, TeachingScheduleService $service): array
    {
        return [
            'type' => TeachingScheduleService::TypePrivate,
            'id' => $request->id,
            'title' => 'Private '.$request->discipline,
            'trainer_id' => $request->trainer_id,
            'trainer_name' => $this->trainerName($request->trainer),
            'room' => null,
            'start_time' => $request->requested_at->format('Y-m-d\TH:i:s'),
            'end_time' => $request->requested_at->copy()->addMinutes($request->userPackage->session_duration_minutes)->format('Y-m-d\TH:i:s'),
            'student_count' => $request->people_count + $request->extra_people_count,
            'max_slot' => null,
            'status' => $service->status($request),
            'has_pending_cancel_request' => (bool) $request->pending_cancel_requests_exists,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapScheduleCancelRequest(ScheduleCancelRequest $request, TeachingScheduleService $service): array
    {
        $schedule = $request->classSchedule
            ? $this->mapTeachingClass($request->classSchedule, $service)
            : $this->mapTeachingPrivate($request->privateRequest, $service);

        return [
            'id' => $request->id,
            'schedule' => $schedule,
            'trainer_name' => $this->trainerName($request->trainer),
            'reason' => $request->reason,
            'status' => $request->status,
            'review_note' => $request->review_note,
            'created_at' => $request->created_at,
        ];
    }
}
