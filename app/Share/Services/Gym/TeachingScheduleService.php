<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\Role;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\ClassSchedule;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;

/**
 * Lịch dạy gồm lớp gói thường đã có người đặt và buổi Private đã được duyệt.
 */
class TeachingScheduleService
{
    public const TypeClass = 'class';

    public const TypePrivate = 'private';

    public function __construct(
        private readonly BookingService $bookingService,
        private readonly PrivateRequestService $privateRequestService,
    ) {}

    /**
     * HLV chỉ tìm thấy lịch của chính mình.
     */
    public function find(string $type, int $id, User $viewer): ClassSchedule|PrivateRequest
    {
        $query = $type === self::TypeClass
            ? ClassSchedule::query()->whereHas('bookings')
            : PrivateRequest::query()
                ->whereNotNull('reviewed_by')
                ->whereIn('status', [BookingRequestStatus::Confirmed, BookingRequestStatus::Completed, BookingRequestStatus::Cancelled]);

        return $query
            ->when($viewer->role->is(Role::Trainer), fn ($inner) => $inner->where('trainer_id', $viewer->id))
            ->findOrFail($id);
    }

    public function status(ClassSchedule|PrivateRequest $target): string
    {
        if ($target instanceof PrivateRequest) {
            return $target->status->value;
        }

        if ($target->status->is(ClassScheduleStatus::Cancelled)) {
            return 'cancelled';
        }

        return $target->end_time->isPast() ? 'completed' : 'confirmed';
    }

    /**
     * Hủy không trừ buổi của học viên.
     */
    public function cancel(ClassSchedule|PrivateRequest $target, User $staff): void
    {
        if ($this->status($target) !== 'confirmed') {
            throw new GymException(__('messages.gym_teaching_not_cancellable'));
        }

        if ($target instanceof ClassSchedule) {
            $this->bookingService->cancelSchedule($target, $staff);

            return;
        }

        $this->privateRequestService->cancelByStaff($target, $staff);
    }
}
