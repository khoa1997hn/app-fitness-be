<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\PackageType;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Yêu cầu lịch gói Private. Buổi và suất người thêm chỉ bị trừ khi admin xác nhận hoàn thành.
 */
class PrivateRequestService
{
    public function __construct(private readonly ExtraPeopleService $extraPeopleService) {}

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

            if ($package->remaining_credits - $this->openRequestCount($package) <= 0) {
                throw new GymException(__('messages.gym_booking_no_credits'));
            }

            return PrivateRequest::query()->create([
                'user_id' => $member->id,
                'user_package_id' => $package->id,
                'discipline' => $data['discipline'],
                'requested_at' => $requestedAt,
                'people_count' => $data['people_count'],
                'extra_people_count' => $extra,
                'note' => $data['note'] ?? null,
                'status' => BookingRequestStatus::Pending,
            ]);
        });
    }

    /**
     * @param  array{discipline: string, requested_at: string, people_count: int, extra_people_count?: int, trainer_id?: int|null, note?: string|null}  $data
     */
    public function update(PrivateRequest $request, array $data): PrivateRequest
    {
        $this->assertStatus($request, BookingRequestStatus::Pending);

        return DB::transaction(function () use ($request, $data) {
            User::query()->lockForUpdate()->findOrFail($request->user_id);

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

        return $request;
    }

    public function reject(PrivateRequest $request, User $staff): PrivateRequest
    {
        $this->assertStatus($request, BookingRequestStatus::Pending);

        $request->update(['status' => BookingRequestStatus::Rejected, 'reviewed_by' => $staff->id]);

        return $request;
    }

    /**
     * Xác nhận buổi đã diễn ra, kể cả khi học viên không đến: trừ 1 buổi và suất người thêm.
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

            /** @var UserPackage $package */
            $package = UserPackage::query()->lockForUpdate()->findOrFail($locked->user_package_id);

            if ($package->remaining_credits !== null && $package->remaining_credits > 0) {
                $package->update(['remaining_credits' => $package->remaining_credits - 1]);
            }

            $locked->update([
                'status' => BookingRequestStatus::Completed,
                'completed_at' => now(),
                'reviewed_by' => $staff->id,
            ]);

            return $locked;
        });
    }

    public function cancelByMember(PrivateRequest $request): PrivateRequest
    {
        if (! $this->canMemberCancel($request)) {
            throw new GymException(__('messages.gym_request_cannot_cancel', ['hours' => config('gym.cancel_lock_hours')]));
        }

        $request->update(['status' => BookingRequestStatus::Cancelled]);

        return $request;
    }

    /**
     * Admin hủy không trừ buổi.
     */
    public function cancelByStaff(PrivateRequest $request, User $staff): PrivateRequest
    {
        $this->assertStatus($request, BookingRequestStatus::Confirmed);

        $request->update(['status' => BookingRequestStatus::Cancelled, 'reviewed_by' => $staff->id]);

        return $request;
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

    private function openRequestCount(UserPackage $package): int
    {
        return PrivateRequest::query()
            ->where('user_package_id', $package->id)
            ->whereIn('status', [BookingRequestStatus::Pending, BookingRequestStatus::Confirmed])
            ->count();
    }

    private function assertStatus(PrivateRequest $request, string $status): void
    {
        if (! $request->status->is($status)) {
            throw new GymException(__('messages.gym_request_invalid_status'));
        }
    }
}
