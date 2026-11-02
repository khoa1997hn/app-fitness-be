<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageStatus;
use App\Share\Enums\Role;
use App\Share\Enums\TherapyVariant;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\Order;
use App\Share\Models\Package;
use App\Share\Models\TherapyService;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use App\Share\Models\UserTherapy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Vòng đời đơn hàng phòng tập: tạo → ký điều khoản → thanh toán → cấp gói.
 * Dùng chung cho đơn hội viên tự đặt và đơn nhân viên tạo hộ.
 */
class OrderService
{
    /**
     * @param  array{type: string, package_id?: int, therapy_service_id?: int, therapy_variant?: string}  $product
     */
    public function create(User $member, array $product, ?User $createdBy = null, ?string $note = null): Order
    {
        if (! $member->role->is(Role::Member)) {
            throw new GymException(__('messages.gym_member_required'));
        }

        return Order::query()->create([
            'code' => 'GYM-'.Str::upper(Str::random(10)),
            'user_id' => $member->id,
            'created_by' => $createdBy?->id,
            'type' => $product['type'],
            'status' => OrderStatus::PendingTerms,
            'note' => $note,
            ...($product['type'] === OrderType::Package
                ? $this->packageFields((int) $product['package_id'])
                : $this->therapyFields((int) $product['therapy_service_id'], $product['therapy_variant'])),
        ]);
    }

    /**
     * @param  array{buyer_name: string, buyer_phone: string, buyer_email: string, signature_data: string}  $terms
     */
    public function acceptTerms(Order $order, array $terms): Order
    {
        if (! $order->status->is(OrderStatus::PendingTerms)) {
            throw new GymException(__('messages.gym_terms_already_accepted'));
        }

        $order->update([
            'status' => OrderStatus::Pending,
            'buyer_name' => $terms['buyer_name'],
            'buyer_phone' => $terms['buyer_phone'],
            'buyer_email' => $terms['buyer_email'],
            'terms_signature' => $terms['signature_data'],
            'terms_accepted_at' => now(),
        ]);

        return $order;
    }

    /**
     * Thanh toán mock: thành công ngay. Gọi lại trên đơn đã thanh toán không cấp gói lần hai.
     */
    public function payWithMock(Order $order, string $paymentMethod): Order
    {
        if (! config('gym.payment_mock_enabled')) {
            throw new GymException(__('messages.gym_payment_unavailable'));
        }

        return DB::transaction(function () use ($order, $paymentMethod) {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->status->is(OrderStatus::Success)) {
                return $locked;
            }

            if ($locked->status->is(OrderStatus::PendingTerms)) {
                throw new GymException(__('messages.gym_terms_required'));
            }

            $locked->update([
                'status' => OrderStatus::Success,
                'payment_method' => $paymentMethod,
                'transaction_code' => 'TXN-'.Str::upper(Str::random(12)),
                'paid_at' => now(),
            ]);

            $this->fulfill($locked);

            return $locked;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function packageFields(int $packageId): array
    {
        $package = Package::query()
            ->where('status', PackageStatus::Active)
            ->find($packageId);

        if (! $package) {
            throw new GymException(__('messages.gym_package_unavailable'));
        }

        return [
            'package_id' => $package->id,
            'name' => $package->name,
            'amount' => $package->price,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function therapyFields(int $therapyServiceId, string $variant): array
    {
        $service = TherapyService::query()->findOrFail($therapyServiceId);
        $isSingle = $variant === TherapyVariant::Single;

        return [
            'therapy_service_id' => $service->id,
            'therapy_variant' => $variant,
            'name' => $service->name.' — '.TherapyVariant::getDescription($variant),
            'amount' => $isSingle ? $service->single_price : $service->combo5_price,
        ];
    }

    private function fulfill(Order $order): void
    {
        if ($order->type->is(OrderType::Package)) {
            $package = $order->package;

            UserPackage::query()->create([
                'user_id' => $order->user_id,
                'package_id' => $package->id,
                'order_id' => $order->id,
                'name' => $package->name,
                'package_type' => $package->package_type,
                'total_credits' => $package->class_credits,
                'remaining_credits' => $package->class_credits,
                'session_duration_minutes' => $package->session_duration_minutes,
                'is_off_peak' => $package->is_off_peak,
                'base_people_max' => $package->base_people_max,
                'extra_people_session_max' => $package->extra_people_session_max,
                'start_date' => today(),
                'end_date' => today()->addDays($package->duration_days),
            ]);

            return;
        }

        $service = $order->therapyService;
        $sessions = $order->therapy_variant->is(TherapyVariant::Single) ? 1 : TherapyService::COMBO5_SESSIONS;

        UserTherapy::query()->create([
            'user_id' => $order->user_id,
            'therapy_service_id' => $service->id,
            'order_id' => $order->id,
            'name' => $order->name,
            'variant' => $order->therapy_variant,
            'sessions_total' => $sessions,
            'sessions_remaining' => $sessions,
            'session_duration_minutes' => $service->single_duration_minutes,
            'end_date' => today()->addDays(config('gym.therapy_validity_days')),
        ]);
    }
}
