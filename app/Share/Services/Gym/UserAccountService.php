<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\NotificationType;
use App\Share\Enums\Role;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\ClassSchedule;
use App\Share\Models\Order;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use App\Share\Models\UserTherapy;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Nghiệp vụ tài khoản phía nhân viên.
 */
class UserAccountService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * Lễ tân và HLV chỉ thao tác trên hội viên, tránh chiếm tài khoản nhân viên khác.
     */
    public function assertCanManage(User $actor, User $target): void
    {
        if (! $actor->role->is(Role::Admin) && ! $target->role->is(Role::Member)) {
            throw new AccessDeniedHttpException;
        }
    }

    public function assertNotSelf(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new GymException(__('messages.gym_user_self_action'));
        }
    }

    /**
     * Tài khoản đã phát sinh dữ liệu phòng tập chỉ được khóa, để giữ lịch sử và báo cáo.
     */
    public function assertDeletable(User $user): void
    {
        $hasGymData = Order::query()->where('user_id', $user->id)->exists()
            || UserPackage::query()->where('user_id', $user->id)->exists()
            || UserTherapy::query()->where('user_id', $user->id)->exists();

        if ($hasGymData) {
            throw new GymException(__('messages.gym_account_has_gym_data'));
        }

        $hasSchedules = ClassSchedule::query()->where('trainer_id', $user->id)->exists()
            || PrivateRequest::query()->where('trainer_id', $user->id)->exists();

        if ($hasSchedules) {
            throw new GymException(__('messages.gym_account_has_schedules'));
        }
    }

    /**
     * Gia hạn cộng ngày vào `end_date`, không thêm buổi.
     */
    public function extendPackage(UserPackage $package, int $extraDays): UserPackage
    {
        return DB::transaction(function () use ($package, $extraDays) {
            /** @var UserPackage $locked */
            $locked = UserPackage::query()->lockForUpdate()->findOrFail($package->id);

            if ($locked->remaining_credits !== null && $locked->remaining_credits <= 0) {
                throw new GymException(__('messages.gym_extend_no_credits'));
            }

            $this->assertExpired($locked);
            $locked->update(['end_date' => $locked->end_date->copy()->addDays($extraDays)]);
            $this->notifyExtended($locked);

            return $locked;
        });
    }

    public function extendTherapy(UserTherapy $therapy, int $extraDays): UserTherapy
    {
        return DB::transaction(function () use ($therapy, $extraDays) {
            /** @var UserTherapy $locked */
            $locked = UserTherapy::query()->lockForUpdate()->findOrFail($therapy->id);

            if ($locked->sessions_remaining <= 0) {
                throw new GymException(__('messages.gym_extend_no_credits'));
            }

            $this->assertExpired($locked);
            $locked->update(['end_date' => $locked->end_date->copy()->addDays($extraDays)]);
            $this->notifyExtended($locked);

            return $locked;
        });
    }

    private function assertExpired(UserPackage|UserTherapy $item): void
    {
        if (! $item->end_date->copy()->endOfDay()->isPast()) {
            throw new GymException(__('messages.gym_extend_not_expired'));
        }
    }

    private function notifyExtended(UserPackage|UserTherapy $item): void
    {
        $this->notificationService->send($item->user_id, NotificationType::Package, __('messages.gym_notify_package_extended', [
            'name' => $item->name,
            'date' => $item->end_date->format('d/m/Y'),
        ]));
    }
}
