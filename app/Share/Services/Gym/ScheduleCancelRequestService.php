<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\ScheduleCancelRequestStatus;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\ClassSchedule;
use App\Share\Models\PrivateRequest;
use App\Share\Models\ScheduleCancelRequest;
use App\Share\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * HLV gửi yêu cầu hủy, kể cả trong vòng 8 tiếng trước giờ dạy. Admin duyệt thì lịch bị hủy, học viên không mất buổi
 * và nhận email hủy lịch. Duyệt hay từ chối đều gửi email kết quả cho HLV.
 */
class ScheduleCancelRequestService
{
    public function __construct(
        private readonly TeachingScheduleService $teachingScheduleService,
        private readonly NotificationService $notificationService,
    ) {}

    public function create(ClassSchedule|PrivateRequest $target, User $trainer, string $reason): ScheduleCancelRequest
    {
        return DB::transaction(function () use ($target, $trainer, $reason) {
            /** @var ClassSchedule|PrivateRequest $locked */
            $locked = $target->newQuery()->lockForUpdate()->findOrFail($target->getKey());
            $startsAt = $locked instanceof ClassSchedule ? $locked->start_time : $locked->requested_at;

            if ($this->teachingScheduleService->status($locked) !== 'confirmed') {
                throw new GymException(__('messages.gym_teaching_not_cancellable'));
            }

            if ($startsAt->isPast()) {
                throw new GymException(__('messages.gym_cancel_request_started'));
            }

            if ($locked->pendingCancelRequests()->exists()) {
                throw new GymException(__('messages.gym_cancel_request_duplicate'));
            }

            return ScheduleCancelRequest::query()->create([
                'class_schedule_id' => $locked instanceof ClassSchedule ? $locked->id : null,
                'private_request_id' => $locked instanceof PrivateRequest ? $locked->id : null,
                'trainer_id' => $trainer->id,
                'reason' => $reason,
                'status' => ScheduleCancelRequestStatus::Pending,
            ]);
        });
    }

    public function approve(ScheduleCancelRequest $request, User $staff, ?string $note): ScheduleCancelRequest
    {
        return DB::transaction(function () use ($request, $staff, $note) {
            $locked = $this->lockPending($request);
            $target = $locked->classSchedule ?? $locked->privateRequest;

            if ($target instanceof ClassSchedule && $target->trainer_id !== $locked->trainer_id) {
                throw new GymException(__('messages.gym_cancel_request_trainer_changed'));
            }

            // Lịch có thể đã bị hủy ở nơi khác trong lúc yêu cầu còn chờ duyệt
            if ($this->teachingScheduleService->status($target) !== 'cancelled') {
                $this->teachingScheduleService->cancel($target, $staff);
            }

            return $this->review($locked, ScheduleCancelRequestStatus::Approved, $staff, $note);
        });
    }

    public function reject(ScheduleCancelRequest $request, User $staff, ?string $note): ScheduleCancelRequest
    {
        return DB::transaction(function () use ($request, $staff, $note) {
            return $this->review($this->lockPending($request), ScheduleCancelRequestStatus::Rejected, $staff, $note);
        });
    }

    private function lockPending(ScheduleCancelRequest $request): ScheduleCancelRequest
    {
        /** @var ScheduleCancelRequest $locked */
        $locked = ScheduleCancelRequest::query()->lockForUpdate()->findOrFail($request->id);

        if (! $locked->status->is(ScheduleCancelRequestStatus::Pending)) {
            throw new GymException(__('messages.gym_cancel_request_reviewed'));
        }

        return $locked;
    }

    private function review(ScheduleCancelRequest $request, string $status, User $staff, ?string $note): ScheduleCancelRequest
    {
        $request->update(['status' => $status, 'reviewed_by' => $staff->id, 'review_note' => $note]);
        $this->notificationService->emailTrainerCancelRequestReviewed($request);

        return $request;
    }
}
