<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\NotificationType;
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
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly EventService $eventService,
        private readonly RedlightService $redlightService,
    ) {}

    /**
     * @param  array{type: string, package_id?: int, therapy_service_id?: int, therapy_variant?: string, event_id?: int, booking_id?: int, quantity?: int}  $product
     */
    public function create(User $member, array $product, ?User $createdBy = null, ?string $note = null): Order
    {
        if (! $member->role->is(Role::Member)) {
            throw new GymException(__('messages.gym_member_required'));
        }

        if ($product['type'] === OrderType::Event) {
            return $this->createEventOrder($member, (int) $product['event_id'], (int) $product['quantity'], $createdBy, $note);
        }

        if ($product['type'] === OrderType::Redlight) {
            return $this->createRedlightOrder($member, (int) $product['booking_id'], $createdBy, $note);
        }

        return Order::query()->create([
            'code' => 'GYM-'.Str::upper(Str::random(10)),
            'user_id' => $member->id,
            'created_by' => $createdBy?->id,
            'type' => $product['type'],
            // Suất người thêm mua trực tiếp, không cần ký điều khoản
            'status' => $product['type'] === OrderType::ExtraPeople ? OrderStatus::Pending : OrderStatus::PendingTerms,
            'note' => $note,
            ...match ($product['type']) {
                OrderType::Package => $this->packageFields((int) $product['package_id']),
                OrderType::Therapy => $this->therapyFields((int) $product['therapy_service_id'], $product['therapy_variant']),
                OrderType::ExtraPeople => $this->extraPeopleFields($member, (int) $product['quantity']),
            },
        ]);
    }

    /**
     * Mua trực tiếp, không cần ký điều khoản. Đã có đơn chờ thanh toán của buổi này thì dùng lại.
     */
    private function createRedlightOrder(User $member, int $bookingId, ?User $createdBy, ?string $note): Order
    {
        return DB::transaction(function () use ($member, $bookingId, $createdBy, $note) {
            $booking = $this->redlightService->lockForPurchase($member, $bookingId);
            $schedule = $booking->classSchedule;

            return $booking->pendingRedlightOrder()->first() ?? Order::query()->create([
                'code' => 'GYM-'.Str::upper(Str::random(10)),
                'user_id' => $member->id,
                'created_by' => $createdBy?->id,
                'type' => OrderType::Redlight,
                'booking_id' => $booking->id,
                'name' => __('messages.gym_redlight_order_name', [
                    'name' => $schedule->fitnessClass->name,
                    'time' => $schedule->start_time->format('H:i d/m/Y'),
                ]),
                'amount' => (int) config('gym.redlight_price'),
                'status' => OrderStatus::Pending,
                'buyer_name' => trim($member->first_name.' '.$member->last_name),
                'buyer_phone' => $member->phone,
                'buyer_email' => $member->email,
                'note' => $note,
            ]);
        });
    }

    /**
     * Giữ vé ngay khi tạo đơn. Khóa dòng sự kiện để không bán vượt số vé.
     */
    private function createEventOrder(User $member, int $eventId, int $quantity, ?User $createdBy, ?string $note): Order
    {
        return DB::transaction(function () use ($member, $eventId, $quantity, $createdBy, $note) {
            $event = $this->eventService->lockForSale($eventId, $member, $quantity);

            if ($event->ticket_price <= 0) {
                throw new GymException(__('messages.gym_event_paid_only'));
            }

            $order = Order::query()->create([
                'code' => 'GYM-'.Str::upper(Str::random(10)),
                'user_id' => $member->id,
                'created_by' => $createdBy?->id,
                'type' => OrderType::Event,
                'event_id' => $event->id,
                'quantity' => $quantity,
                'name' => __('messages.gym_event_order_name', ['name' => $event->name, 'quantity' => $quantity]),
                'amount' => $event->ticket_price * $quantity,
                'status' => OrderStatus::PendingTerms,
                'note' => $note,
            ]);

            $this->eventService->hold($event, $member, $order, $quantity);

            return $order;
        });
    }

    /**
     * @param  array{buyer_name: string, buyer_phone: string, buyer_email: string, signature_data: string}  $terms
     */
    public function acceptTerms(Order $order, array $terms): Order
    {
        if ($order->status->is(OrderStatus::Cancelled)) {
            throw new GymException(__('messages.gym_order_cancelled'));
        }

        if (! $order->status->is(OrderStatus::PendingTerms)) {
            throw new GymException(__('messages.gym_terms_already_accepted'));
        }

        if ($order->type->is(OrderType::Event)) {
            $this->eventService->assertHoldValid($order);
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

            if ($locked->status->is(OrderStatus::Cancelled)) {
                throw new GymException(__('messages.gym_order_cancelled'));
            }

            if ($locked->status->is(OrderStatus::PendingTerms)) {
                throw new GymException(__('messages.gym_terms_required'));
            }

            if ($locked->type->is(OrderType::Event)) {
                $this->eventService->assertHoldValid($locked);
            }

            if ($locked->type->is(OrderType::Redlight)) {
                $this->redlightService->assertPayable($locked);
            }

            $locked->update([
                'status' => OrderStatus::Success,
                'payment_method' => $paymentMethod,
                'transaction_code' => 'TXN-'.Str::upper(Str::random(12)),
                'paid_at' => now(),
            ]);

            $this->fulfill($locked);

            // Thông báo vé kèm mã vé do EventService gửi
            if (! $locked->type->is(OrderType::Event)) {
                $this->notificationService->send(
                    $locked->user_id,
                    NotificationType::Payment,
                    __('messages.gym_notify_payment_success', ['name' => $locked->name]),
                );
            }

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

    /**
     * @return array<string, mixed>
     */
    private function extraPeopleFields(User $member, int $quantity): array
    {
        return [
            'quantity' => $quantity,
            'name' => __('messages.gym_extra_people_order_name', ['quantity' => $quantity]),
            'amount' => $quantity * (int) config('gym.extra_people_price'),
            'buyer_name' => trim($member->first_name.' '.$member->last_name),
            'buyer_phone' => $member->phone,
            'buyer_email' => $member->email,
        ];
    }

    private function fulfill(Order $order): void
    {
        // Số dư suất người thêm tính trực tiếp từ đơn đã thanh toán
        if ($order->type->is(OrderType::ExtraPeople)) {
            return;
        }

        if ($order->type->is(OrderType::Event)) {
            $this->eventService->markPaid($order);

            return;
        }

        if ($order->type->is(OrderType::Redlight)) {
            $this->redlightService->markPaid($order);

            return;
        }

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
