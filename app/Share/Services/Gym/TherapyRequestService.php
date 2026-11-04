<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\NotificationType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\TherapyRequest;
use App\Share\Models\User;
use App\Share\Models\UserTherapy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Yêu cầu lịch trị liệu. Buổi bị trừ khi admin duyệt, hoàn lại nếu lịch đã duyệt bị hủy hợp lệ.
 */
class TherapyRequestService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * @param  array{user_therapy_id: int, preferred_at: string, note?: string|null}  $data
     */
    public function create(User $member, array $data): TherapyRequest
    {
        return DB::transaction(function () use ($member, $data) {
            /** @var UserTherapy $therapy */
            $therapy = UserTherapy::query()
                ->where('user_id', $member->id)
                ->lockForUpdate()
                ->findOrFail($data['user_therapy_id']);

            $preferredAt = Carbon::parse($data['preferred_at']);
            $this->assertTime($therapy, $preferredAt);

            $pending = TherapyRequest::query()
                ->where('user_therapy_id', $therapy->id)
                ->where('status', BookingRequestStatus::Pending)
                ->count();

            if ($therapy->sessions_remaining - $pending <= 0) {
                throw new GymException(__('messages.gym_booking_no_credits'));
            }

            return TherapyRequest::query()->create([
                'user_id' => $member->id,
                'user_therapy_id' => $therapy->id,
                'preferred_at' => $preferredAt,
                'note' => $data['note'] ?? null,
                'status' => BookingRequestStatus::Pending,
            ]);
        });
    }

    /**
     * @param  array{preferred_at: string, note?: string|null}  $data
     */
    public function update(TherapyRequest $request, array $data): TherapyRequest
    {
        $this->assertStatus($request, BookingRequestStatus::Pending);

        $preferredAt = Carbon::parse($data['preferred_at']);
        $this->assertTime($request->userTherapy, $preferredAt);

        $request->update(['preferred_at' => $preferredAt, 'note' => $data['note'] ?? null]);

        return $request;
    }

    public function approve(TherapyRequest $request, User $staff): TherapyRequest
    {
        return DB::transaction(function () use ($request, $staff) {
            /** @var TherapyRequest $locked */
            $locked = TherapyRequest::query()->lockForUpdate()->findOrFail($request->id);
            $this->assertStatus($locked, BookingRequestStatus::Pending);

            if ($locked->preferred_at->isPast()) {
                throw new GymException(__('messages.gym_request_time_passed'));
            }

            /** @var UserTherapy $therapy */
            $therapy = UserTherapy::query()->lockForUpdate()->findOrFail($locked->user_therapy_id);

            if ($therapy->sessions_remaining <= 0) {
                throw new GymException(__('messages.gym_booking_no_credits'));
            }

            $therapy->update(['sessions_remaining' => $therapy->sessions_remaining - 1]);
            $locked->update(['status' => BookingRequestStatus::Confirmed, 'reviewed_by' => $staff->id]);
            $this->notify($locked, 'messages.gym_notify_request_approved');

            return $locked;
        });
    }

    public function reject(TherapyRequest $request, User $staff): TherapyRequest
    {
        $this->assertStatus($request, BookingRequestStatus::Pending);

        $request->update(['status' => BookingRequestStatus::Rejected, 'reviewed_by' => $staff->id]);
        $this->notify($request, 'messages.gym_notify_request_rejected');

        return $request;
    }

    public function cancelByMember(TherapyRequest $request): TherapyRequest
    {
        if (! $this->canMemberCancel($request)) {
            throw new GymException(__('messages.gym_request_cannot_cancel', ['hours' => config('gym.cancel_lock_hours')]));
        }

        return $this->cancel($request);
    }

    /**
     * Admin hủy lịch đã duyệt khi buổi chưa bắt đầu: hoàn buổi.
     */
    public function cancelByStaff(TherapyRequest $request, User $staff): TherapyRequest
    {
        $this->assertStatus($request, BookingRequestStatus::Confirmed);

        if ($request->preferred_at->isPast()) {
            throw new GymException(__('messages.gym_request_started_cannot_cancel'));
        }

        $cancelled = $this->cancel($request, $staff);
        $this->notificationService->sendScheduleCancelled(
            $cancelled->user_id,
            $this->message($cancelled, 'messages.gym_notify_therapy_cancelled'),
            messageEn: $this->message($cancelled, 'messages.gym_notify_therapy_cancelled', 'en'),
            messageVi: $this->message($cancelled, 'messages.gym_notify_therapy_cancelled', 'vi'),
        );

        return $cancelled;
    }

    private function notify(TherapyRequest $request, string $messageKey): void
    {
        $this->notificationService->send($request->user_id, NotificationType::Schedule, $this->message($request, $messageKey));
    }

    private function message(TherapyRequest $request, string $messageKey, ?string $locale = null): string
    {
        return __($messageKey, [
            'name' => $request->userTherapy->name,
            'time' => $request->preferred_at->format('H:i d/m/Y'),
        ], $locale);
    }

    public function canMemberCancel(TherapyRequest $request): bool
    {
        return $request->status->is(BookingRequestStatus::Pending)
            || ($request->status->is(BookingRequestStatus::Confirmed)
                && now()->addHours((int) config('gym.cancel_lock_hours'))->lte($request->preferred_at));
    }

    private function cancel(TherapyRequest $request, ?User $staff = null): TherapyRequest
    {
        return DB::transaction(function () use ($request, $staff) {
            /** @var TherapyRequest $locked */
            $locked = TherapyRequest::query()->lockForUpdate()->findOrFail($request->id);
            $wasConfirmed = $locked->status->is(BookingRequestStatus::Confirmed);

            $locked->update([
                'status' => BookingRequestStatus::Cancelled,
                'reviewed_by' => $staff?->id ?? $locked->reviewed_by,
            ]);

            if ($wasConfirmed) {
                UserTherapy::query()->whereKey($locked->user_therapy_id)->increment('sessions_remaining');
            }

            return $locked;
        });
    }

    private function assertTime(UserTherapy $therapy, Carbon $preferredAt): void
    {
        if ($preferredAt->isPast()) {
            throw new GymException(__('messages.gym_request_time_passed'));
        }

        if ($therapy->end_date->endOfDay()->lt($preferredAt)) {
            throw new GymException(__('messages.gym_booking_package_expired'));
        }
    }

    private function assertStatus(TherapyRequest $request, string $status): void
    {
        if (! $request->status->is($status)) {
            throw new GymException(__('messages.gym_request_invalid_status'));
        }
    }
}
