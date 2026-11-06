<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\NotificationType;
use App\Share\Enums\PackageType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Yêu cầu lịch gói Private. Buổi bị trừ ngay khi gửi đơn, từ chối hoặc hủy hợp lệ thì hoàn lại.
 * Suất người thêm được giữ chỗ khi gửi đơn và chỉ tính là đã dùng khi buổi hoàn thành.
 * Buổi đã duyệt tự hoàn thành khi qua giờ bắt đầu; admin vẫn bỏ hoàn thành hoặc hủy được.
 */
class PrivateRequestService
{
    public function __construct(
        private readonly ExtraPeopleService $extraPeopleService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * @param  array{user_package_id: int, discipline: string, requested_at: string, people_count: int, extra_people_count?: int, note?: string|null}  $data
     */
    public function create(User $member, array $data): PrivateRequest
    {
        return DB::transaction(function () use ($member, $data) {
            User::query()->lockForUpdate()->findOrFail($member->id);
            /** @var UserPackage $package */
            $package = UserPackage::query()
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->findOrFail($data['user_package_id']);

            $extra = (int) ($data['extra_people_count'] ?? 0);
            $requestedAt = Carbon::parse($data['requested_at']);

            $this->assertValid($member, $package, $requestedAt, (int) $data['people_count'], $extra);

            if ($package->remaining_credits !== null) {
                if ($package->remaining_credits <= 0) {
                    throw new GymException(__('messages.gym_booking_no_credits'));
                }

                $package->update(['remaining_credits' => $package->remaining_credits - 1]);
            }

            $request = PrivateRequest::query()->create([
                'user_id' => $member->id,
                'user_package_id' => $package->id,
                'discipline' => $data['discipline'],
                'requested_at' => $requestedAt,
                'people_count' => $data['people_count'],
                'extra_people_count' => $extra,
                'note' => $data['note'] ?? null,
                'status' => BookingRequestStatus::Pending,
            ]);

            $this->notificationService->emailAdminsPrivatePending($request);

            return $request;
        });
    }

    /**
     * Sửa được khi còn chờ duyệt, hoặc đã duyệt mà buổi chưa bắt đầu. Sửa lịch đã duyệt thì báo cho học viên.
     *
     * @param  array{discipline: string, requested_at: string, people_count: int, extra_people_count?: int, trainer_id?: int|null, note?: string|null}  $data
     */
    public function update(PrivateRequest $request, array $data): PrivateRequest
    {
        return DB::transaction(function () use ($request, $data) {
            User::query()->lockForUpdate()->findOrFail($request->user_id);
            /** @var PrivateRequest $request */
            $request = PrivateRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! $request->status->in([BookingRequestStatus::Pending, BookingRequestStatus::Confirmed])) {
                throw new GymException(__('messages.gym_request_invalid_status'));
            }

            if ($request->status->is(BookingRequestStatus::Confirmed) && $request->requested_at->isPast()) {
                throw new GymException(__('messages.gym_request_already_started'));
            }

            $extra = (int) ($data['extra_people_count'] ?? 0);
            $requestedAt = Carbon::parse($data['requested_at']);

            $this->assertValid($request->user, $request->userPackage, $requestedAt, (int) $data['people_count'], $extra, $request->id);

            $request->update([
                'discipline' => $data['discipline'],
                'requested_at' => $requestedAt,
                'people_count' => $data['people_count'],
                'extra_people_count' => $extra,
                'trainer_id' => $data['trainer_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            if ($request->status->is(BookingRequestStatus::Confirmed) && $request->wasChanged()) {
                $this->notify($request, 'messages.gym_notify_request_updated');

                if ($request->wasChanged('trainer_id')) {
                    $this->emailTrainer($request);
                }
            }

            return $request;
        });
    }

    public function approve(PrivateRequest $request, User $staff): PrivateRequest
    {
        $this->assertStatus($request, BookingRequestStatus::Pending);

        if ($request->requested_at->isPast()) {
            throw new GymException(__('messages.gym_request_time_passed'));
        }

        $request->update(['status' => BookingRequestStatus::Confirmed, 'reviewed_by' => $staff->id]);
        $this->notify($request, 'messages.gym_notify_request_approved');
        $this->emailTrainer($request);

        return $request;
    }

    public function reject(PrivateRequest $request, User $staff): PrivateRequest
    {
        return DB::transaction(function () use ($request, $staff) {
            /** @var PrivateRequest $locked */
            $locked = PrivateRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->assertStatus($locked, BookingRequestStatus::Pending);

            $locked->update(['status' => BookingRequestStatus::Rejected, 'reviewed_by' => $staff->id]);
            $this->refundCredit($locked);
            $this->notify($locked, 'messages.gym_notify_private_rejected');

            return $locked;
        });
    }

    /**
     * Xác nhận buổi đã diễn ra, kể cả khi học viên không đến. Buổi đã trừ lúc gửi đơn nên không trừ thêm, suất người thêm tính là đã dùng.
     */
    public function complete(PrivateRequest $request, User $staff): PrivateRequest
    {
        return DB::transaction(function () use ($request, $staff) {
            /** @var PrivateRequest $locked */
            $locked = PrivateRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->assertStatus($locked, BookingRequestStatus::Confirmed);

            if ($locked->requested_at->isFuture()) {
                throw new GymException(__('messages.gym_request_not_started'));
            }

            $locked->update([
                'status' => BookingRequestStatus::Completed,
                'completed_at' => now(),
                'reviewed_by' => $staff->id,
            ]);

            return $locked;
        });
    }

    /**
     * Tự hoàn thành các buổi đã duyệt đã qua giờ bắt đầu, trừ buổi admin đã bỏ hoàn thành.
     */
    public function completeStarted(): int
    {
        return PrivateRequest::query()
            ->where('status', BookingRequestStatus::Confirmed)
            ->where('requested_at', '<=', now())
            ->where('skip_auto_complete', false)
            ->update(['status' => BookingRequestStatus::Completed, 'completed_at' => now()]);
    }

    /**
     * Đưa buổi đã hoàn thành về đã duyệt để admin xử lý lại. Hệ thống không tự hoàn thành lại buổi này.
     */
    public function uncomplete(PrivateRequest $request, User $staff): PrivateRequest
    {
        return DB::transaction(function () use ($request, $staff) {
            /** @var PrivateRequest $locked */
            $locked = PrivateRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->assertStatus($locked, BookingRequestStatus::Completed);

            $locked->update([
                'status' => BookingRequestStatus::Confirmed,
                'completed_at' => null,
                'skip_auto_complete' => true,
                'reviewed_by' => $staff->id,
            ]);

            return $locked;
        });
    }

    public function cancelByMember(PrivateRequest $request): PrivateRequest
    {
        return DB::transaction(function () use ($request) {
            /** @var PrivateRequest $locked */
            $locked = PrivateRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! $this->canMemberCancel($locked)) {
                throw new GymException(__('messages.gym_request_cannot_cancel', ['hours' => config('gym.cancel_lock_hours')]));
            }

            $locked->update(['status' => BookingRequestStatus::Cancelled]);
            $this->refundCredit($locked);

            return $locked;
        });
    }

    /**
     * Admin hủy lịch đã duyệt hoặc đã hoàn thành thì hoàn lại buổi đã trừ lúc gửi đơn và nhả suất người thêm.
     */
    public function cancelByStaff(PrivateRequest $request, User $staff): PrivateRequest
    {
        return DB::transaction(function () use ($request, $staff) {
            /** @var PrivateRequest $locked */
            $locked = PrivateRequest::query()->lockForUpdate()->findOrFail($request->id);
            if (! $locked->status->in([BookingRequestStatus::Confirmed, BookingRequestStatus::Completed])) {
                throw new GymException(__('messages.gym_request_invalid_status'));
            }

            $locked->update(['status' => BookingRequestStatus::Cancelled, 'completed_at' => null, 'reviewed_by' => $staff->id]);
            $this->refundCredit($locked);
            $this->notificationService->sendScheduleCancelled(
                $locked->user_id,
                $this->message($locked, 'messages.gym_notify_booking_cancelled'),
                messageEn: $this->message($locked, 'messages.gym_notify_booking_cancelled', 'en'),
                messageVi: $this->message($locked, 'messages.gym_notify_booking_cancelled', 'vi'),
            );

            return $locked;
        });
    }

    private function refundCredit(PrivateRequest $request): void
    {
        /** @var UserPackage $package */
        $package = UserPackage::query()->lockForUpdate()->findOrFail($request->user_package_id);

        if ($package->remaining_credits !== null) {
            $package->update(['remaining_credits' => $package->remaining_credits + 1]);
        }
    }

    private function emailTrainer(PrivateRequest $request): void
    {
        if ($request->trainer_id !== null) {
            $this->notificationService->emailTrainerAssigned($request->trainer_id, 'Private '.$request->discipline, $request->requested_at);
        }
    }

    private function notify(PrivateRequest $request, string $messageKey): void
    {
        $this->notificationService->send($request->user_id, NotificationType::Schedule, $this->message($request, $messageKey));
    }

    private function message(PrivateRequest $request, string $messageKey, ?string $locale = null): string
    {
        return __($messageKey, [
            'name' => 'Private '.$request->discipline,
            'time' => $request->requested_at->format('H:i d/m/Y'),
        ], $locale);
    }

    public function canMemberCancel(PrivateRequest $request): bool
    {
        return $request->status->is(BookingRequestStatus::Pending)
            || ($request->status->is(BookingRequestStatus::Confirmed)
                && now()->addHours((int) config('gym.cancel_lock_hours'))->lte($request->requested_at));
    }

    private function assertValid(User $member, UserPackage $package, Carbon $requestedAt, int $people, int $extra, ?int $exceptRequestId = null): void
    {
        if (! $package->package_type->in([PackageType::PrivatePack, PackageType::PrivateGroup])) {
            throw new GymException(__('messages.gym_request_not_private'));
        }

        if ($requestedAt->isPast()) {
            throw new GymException(__('messages.gym_request_time_passed'));
        }

        if ($package->end_date->endOfDay()->lt($requestedAt)) {
            throw new GymException(__('messages.gym_booking_package_expired'));
        }

        if ($people > $package->base_people_max) {
            throw new GymException(__('messages.gym_request_people_limit', ['max' => $package->base_people_max]));
        }

        if ($extra === 0) {
            return;
        }

        if ($package->package_type->is(PackageType::PrivateGroup)) {
            throw new GymException(__('messages.gym_request_extra_not_allowed'));
        }

        if ($extra > $package->extra_people_session_max) {
            throw new GymException(__('messages.gym_request_extra_session_limit', ['max' => $package->extra_people_session_max]));
        }

        if ($extra > $this->extraPeopleService->summary($member, $exceptRequestId)['available']) {
            throw new GymException(__('messages.gym_request_extra_insufficient'));
        }
    }

    private function assertStatus(PrivateRequest $request, string $status): void
    {
        if (! $request->status->is($status)) {
            throw new GymException(__('messages.gym_request_invalid_status'));
        }
    }
}
