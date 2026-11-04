<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageType;
use App\Share\Enums\PaymentMethod;
use App\Share\Enums\Role;
use App\Share\Enums\TherapyVariant;
use App\Share\Models\Order;
use App\Share\Models\Package;
use App\Share\Models\TherapyService;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use App\Share\Models\UserTherapy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private const NEXT_WEEK = '2026-11-17T15:00';

    private User $member;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->member = User::factory()->create();
        $this->admin = User::factory()->create(['role' => Role::Admin]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createPrivatePackage(string $type = PackageType::PrivatePack, array $overrides = [], ?User $user = null): UserPackage
    {
        $user ??= $this->member;
        $package = Package::query()->create([
            'package_type' => $type,
            'price' => 7000000,
            'duration_days' => 180,
            'class_credits' => 10,
            'session_duration_minutes' => 60,
            'vi' => ['name' => 'Private 10 buổi'],
        ]);
        $order = Order::query()->create([
            'code' => 'GYM-'.uniqid(),
            'user_id' => $user->id,
            'type' => OrderType::Package,
            'package_id' => $package->id,
            'name' => 'Private 10 buổi',
            'amount' => 7000000,
            'status' => OrderStatus::Success,
        ]);

        return UserPackage::query()->create(array_merge([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'order_id' => $order->id,
            'name' => 'Private 10 buổi',
            'package_type' => $type,
            'total_credits' => 10,
            'remaining_credits' => 10,
            'session_duration_minutes' => 60,
            'is_off_peak' => false,
            'base_people_max' => 2,
            'extra_people_session_max' => 3,
            'start_date' => '2026-11-01',
            'end_date' => '2027-04-01',
        ], $overrides));
    }

    private function createTherapy(array $overrides = [], ?User $user = null): UserTherapy
    {
        $user ??= $this->member;
        $service = TherapyService::query()->firstOrCreate(['slug' => 'deep-stretch'], [
            'single_price' => 500000,
            'single_duration_minutes' => 60,
            'combo5_price' => 2000000,
            'vi' => ['name' => 'Deep Stretch'],
        ]);
        $order = Order::query()->create([
            'code' => 'GYM-'.uniqid(),
            'user_id' => $user->id,
            'type' => OrderType::Therapy,
            'therapy_service_id' => $service->id,
            'therapy_variant' => TherapyVariant::Combo5,
            'name' => 'Deep Stretch — Combo 5 buổi',
            'amount' => 2000000,
            'status' => OrderStatus::Success,
        ]);

        return UserTherapy::query()->create(array_merge([
            'user_id' => $user->id,
            'therapy_service_id' => $service->id,
            'order_id' => $order->id,
            'name' => 'Deep Stretch — Combo 5 buổi',
            'variant' => TherapyVariant::Combo5,
            'sessions_total' => 5,
            'sessions_remaining' => 5,
            'session_duration_minutes' => 60,
            'end_date' => '2027-02-01',
        ], $overrides));
    }

    private function asMember(?User $user = null): static
    {
        auth('api')->forgetUser();

        return $this->withToken(auth('api')->login($user ?? $this->member));
    }

    private function asAdmin(): static
    {
        auth('api')->forgetUser();

        return $this->withToken(auth('api')->login($this->admin));
    }

    private function buyExtraPeople(int $quantity): void
    {
        $client = $this->asMember();
        $code = $client->postJson('/api/v1/orders', ['type' => OrderType::ExtraPeople, 'quantity' => $quantity])
            ->assertCreated()
            ->json('data.code');
        $client->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => PaymentMethod::Momo])->assertOk();
    }

    private function submitPrivate(UserPackage $package, array $overrides = [])
    {
        return $this->asMember()->postJson('/api/v1/private-requests', array_merge([
            'user_package_id' => $package->id,
            'discipline' => 'Yoga',
            'requested_at' => self::NEXT_WEEK,
            'people_count' => 2,
        ], $overrides));
    }

    public function test_extra_people_purchase_skips_terms_and_counts_after_payment(): void
    {
        $client = $this->asMember();
        $order = $client->postJson('/api/v1/orders', ['type' => OrderType::ExtraPeople, 'quantity' => 5])
            ->assertCreated()
            ->assertJsonPath('data.status', OrderStatus::Pending)
            ->assertJsonPath('data.amount', 1000000)
            ->assertJsonPath('data.quantity', 5)
            ->json('data');

        $client->getJson('/api/v1/users/me/extra-people')
            ->assertJsonPath('data.available', 0)
            ->assertJsonPath('data.price_per_slot', 200000);

        $client->postJson('/api/v1/payments', ['order_code' => $order['code'], 'payment_method' => PaymentMethod::Momo])->assertOk();

        $client->getJson('/api/v1/users/me/extra-people')
            ->assertJsonPath('data.purchased_total', 5)
            ->assertJsonPath('data.balance', 5)
            ->assertJsonPath('data.available', 5);
    }

    public function test_extra_people_quantity_is_required(): void
    {
        $this->asMember()->postJson('/api/v1/orders', ['type' => OrderType::ExtraPeople])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_private_pack_uses_account_balance_shared_across_packages(): void
    {
        $this->buyExtraPeople(3);
        $first = $this->createPrivatePackage();
        $second = $this->createPrivatePackage();

        $this->submitPrivate($first, ['extra_people_count' => 2])->assertCreated();
        $this->asMember()->getJson('/api/v1/users/me/extra-people')
            ->assertJsonPath('data.reserved', 2)
            ->assertJsonPath('data.available', 1);

        $this->submitPrivate($second, ['extra_people_count' => 2])->assertUnprocessable();
        $this->submitPrivate($second, ['extra_people_count' => 1])->assertCreated();
    }

    public function test_private_group_cannot_add_people(): void
    {
        $this->buyExtraPeople(3);
        $group = $this->createPrivatePackage(PackageType::PrivateGroup, ['base_people_max' => 15, 'total_credits' => 1, 'remaining_credits' => 1]);

        $this->submitPrivate($group, ['extra_people_count' => 1])->assertUnprocessable();
        $this->submitPrivate($group, ['people_count' => 15])->assertCreated();
    }

    public function test_private_limits(): void
    {
        $this->buyExtraPeople(10);
        $package = $this->createPrivatePackage();

        $this->submitPrivate($package, ['people_count' => 3])->assertUnprocessable();
        $this->submitPrivate($package, ['extra_people_count' => 4])->assertUnprocessable();
        $this->submitPrivate($package, ['requested_at' => '2026-11-10T09:00'])->assertUnprocessable();
        $this->submitPrivate($package, ['requested_at' => '2027-04-02T09:00'])->assertUnprocessable();
        $this->submitPrivate($package, ['discipline' => 'Boxing'])->assertUnprocessable();
    }

    public function test_cannot_request_more_than_remaining_sessions(): void
    {
        $package = $this->createPrivatePackage(overrides: ['total_credits' => 1, 'remaining_credits' => 1]);

        $this->submitPrivate($package)->assertCreated();
        $this->submitPrivate($package)->assertUnprocessable();
    }

    public function test_regular_package_or_other_users_package_rejected(): void
    {
        $this->submitPrivate($this->createPrivatePackage(PackageType::ClassPack))->assertUnprocessable();
        $this->submitPrivate($this->createPrivatePackage(user: User::factory()->create()))->assertNotFound();
    }

    public function test_full_private_flow_deducts_only_on_complete_even_for_no_show(): void
    {
        $this->buyExtraPeople(2);
        $package = $this->createPrivatePackage();
        $id = $this->submitPrivate($package, ['extra_people_count' => 2])
            ->assertJsonPath('data.status', BookingRequestStatus::Pending)
            ->json('data.id');

        $admin = $this->asAdmin();
        $admin->patchJson("/api/v1/admin/private-requests/{$id}/complete")->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/private-requests/{$id}/approve")->assertOk()->assertJsonPath('data.status', BookingRequestStatus::Confirmed);
        $admin->patchJson("/api/v1/admin/private-requests/{$id}/complete")->assertUnprocessable();

        $this->assertSame(10, $package->refresh()->remaining_credits);

        Carbon::setTestNow('2026-11-17 16:00:00');
        $admin = $this->asAdmin();
        $admin->patchJson("/api/v1/admin/private-requests/{$id}/complete")->assertOk()->assertJsonPath('data.status', BookingRequestStatus::Completed);
        $admin->patchJson("/api/v1/admin/private-requests/{$id}/complete")->assertUnprocessable();

        $this->assertSame(9, $package->refresh()->remaining_credits);
        $this->asMember()->getJson('/api/v1/users/me/extra-people')
            ->assertJsonPath('data.used', 2)
            ->assertJsonPath('data.reserved', 0)
            ->assertJsonPath('data.balance', 0);
    }

    public function test_reject_returns_reserved_people(): void
    {
        $this->buyExtraPeople(2);
        $id = $this->submitPrivate($this->createPrivatePackage(), ['extra_people_count' => 2])->json('data.id');

        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}/reject")->assertOk();

        $this->asMember()->getJson('/api/v1/users/me/extra-people')->assertJsonPath('data.available', 2);
        $this->asMember()->getJson('/api/v1/users/me/private-requests')->assertJsonPath('data.0.status', BookingRequestStatus::Rejected);
    }

    public function test_private_cancel_rules(): void
    {
        $package = $this->createPrivatePackage();
        $pending = $this->submitPrivate($package)->json('data.id');
        $this->asMember()->patchJson("/api/v1/private-requests/{$pending}/cancel")->assertOk();
        $this->asMember()->getJson('/api/v1/users/me/private-requests')->assertJsonCount(0, 'data');

        $locked = $this->submitPrivate($package, ['requested_at' => '2026-11-10T17:59'])->json('data.id');
        $open = $this->submitPrivate($package, ['requested_at' => '2026-11-10T18:00'])->json('data.id');
        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$locked}/approve")->assertOk();
        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$open}/approve")->assertOk();

        $this->asMember()->patchJson("/api/v1/private-requests/{$locked}/cancel")->assertUnprocessable();
        $this->asMember()->patchJson("/api/v1/private-requests/{$open}/cancel")->assertOk();

        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$locked}/cancel")->assertOk();
        $this->assertSame(10, $package->refresh()->remaining_credits);
    }

    public function test_admin_edits_private_request_with_same_rules(): void
    {
        $this->buyExtraPeople(1);
        $trainer = User::factory()->create(['role' => Role::Trainer, 'first_name' => 'Thu', 'last_name' => 'Hà']);
        $id = $this->submitPrivate($this->createPrivatePackage(), ['extra_people_count' => 1])->json('data.id');
        $body = ['discipline' => 'Barre', 'requested_at' => '2026-11-18T09:00', 'people_count' => 1, 'extra_people_count' => 1, 'trainer_id' => $trainer->id];

        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}", $body)
            ->assertOk()
            ->assertJsonPath('data.discipline', 'Barre')
            ->assertJsonPath('data.trainer_name', 'Thu Hà')
            ->assertJsonPath('data.requested_at', '2026-11-18T09:00:00');

        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}", [...$body, 'extra_people_count' => 2])->assertUnprocessable();
        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}", [...$body, 'trainer_id' => $this->member->id])->assertUnprocessable();
    }

    public function test_admin_edits_confirmed_private_and_member_is_notified(): void
    {
        $trainer = User::factory()->create(['role' => Role::Trainer, 'first_name' => 'Thu', 'last_name' => 'Hà']);
        $id = $this->submitPrivate($this->createPrivatePackage())->json('data.id');
        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}/approve")->assertOk();
        $body = ['discipline' => 'Barre', 'requested_at' => '2026-11-18T09:00', 'people_count' => 1, 'trainer_id' => $trainer->id];
        $notifications = fn () => DB::table('notifications')->where('user_id', $this->member->id)->where('message', 'like', '%cập nhật%')->count();

        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}", $body)
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.discipline', 'Barre')
            ->assertJsonPath('data.trainer_name', 'Thu Hà')
            ->assertJsonPath('data.requested_at', '2026-11-18T09:00:00');
        $this->assertSame(1, $notifications());

        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}", $body)->assertOk();
        $this->assertSame(1, $notifications());

        Carbon::setTestNow('2026-11-18 09:00:01');
        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}", [...$body, 'requested_at' => '2026-11-20T09:00'])->assertUnprocessable();

        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}/complete")->assertOk();
        $this->asAdmin()->patchJson("/api/v1/admin/private-requests/{$id}", [...$body, 'requested_at' => '2026-11-20T09:00'])->assertUnprocessable();
    }

    public function test_therapy_deducts_on_approve_and_refunds_on_valid_cancel(): void
    {
        $therapy = $this->createTherapy();
        $id = $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK])
            ->assertCreated()
            ->assertJsonPath('data.status', BookingRequestStatus::Pending)
            ->json('data.id');

        $this->assertSame(5, $therapy->refresh()->sessions_remaining);

        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/approve")->assertOk();
        $this->assertSame(4, $therapy->refresh()->sessions_remaining);

        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/approve")->assertUnprocessable();
        $this->assertSame(4, $therapy->refresh()->sessions_remaining);

        $this->asMember()->patchJson("/api/v1/therapy-requests/{$id}/cancel")->assertOk();
        $this->assertSame(5, $therapy->refresh()->sessions_remaining);
    }

    public function test_therapy_cancel_blocked_within_8_hours_and_keeps_deduction(): void
    {
        $therapy = $this->createTherapy();
        $id = $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => '2026-11-10T17:00'])->json('data.id');
        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/approve")->assertOk();

        $this->asMember()->patchJson("/api/v1/therapy-requests/{$id}/cancel")->assertUnprocessable();
        $this->assertSame(4, $therapy->refresh()->sessions_remaining);

        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/cancel")->assertOk();
        $this->assertSame(5, $therapy->refresh()->sessions_remaining);
    }

    public function test_admin_cannot_cancel_therapy_after_it_started(): void
    {
        $therapy = $this->createTherapy();
        $id = $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK])->json('data.id');
        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/approve")->assertOk();

        Carbon::setTestNow('2026-11-17 15:00:01');

        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/cancel")->assertUnprocessable();
        $this->assertSame(4, $therapy->refresh()->sessions_remaining);
    }

    public function test_admin_cancel_therapy_notifies_refund(): void
    {
        $therapy = $this->createTherapy();
        $id = $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK])->json('data.id');
        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/approve")->assertOk();
        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/cancel")->assertOk();

        $this->assertSame(5, $therapy->refresh()->sessions_remaining);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $therapy->user_id,
            'message' => __('messages.gym_notify_therapy_cancelled', ['name' => $therapy->name, 'time' => '15:00 17/11/2026']),
        ]);
    }

    public function test_admin_therapy_list_filters_by_status_and_date(): void
    {
        $therapy = $this->createTherapy();
        $confirmed = $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK])->json('data.id');
        $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => '2026-11-18T15:00'])->assertCreated();
        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$confirmed}/approve")->assertOk();

        $admin = $this->asAdmin();
        $admin->getJson('/api/v1/admin/therapy-requests')->assertOk()->assertJsonCount(2, 'data');
        $admin->getJson('/api/v1/admin/therapy-requests?status=confirmed')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $confirmed);
        $admin->getJson('/api/v1/admin/therapy-requests?date=2026-11-17')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $confirmed);
        $admin->getJson('/api/v1/admin/therapy-requests?date=2026-11-19')->assertJsonCount(0, 'data');
        $admin->getJson('/api/v1/admin/therapy-requests?date=17-11-2026')->assertUnprocessable();
    }

    public function test_therapy_pending_cannot_exceed_remaining(): void
    {
        $therapy = $this->createTherapy(['sessions_total' => 1, 'sessions_remaining' => 1]);
        $body = ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK];

        $this->asMember()->postJson('/api/v1/therapy-requests', $body)->assertCreated();
        $this->asMember()->postJson('/api/v1/therapy-requests', $body)->assertUnprocessable();
    }

    public function test_reject_therapy_keeps_sessions_and_lists_for_member(): void
    {
        $therapy = $this->createTherapy();
        $id = $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK])->json('data.id');

        $this->asAdmin()->patchJson("/api/v1/admin/therapy-requests/{$id}/reject")->assertOk();

        $this->assertSame(5, $therapy->refresh()->sessions_remaining);
        $this->asMember()->getJson('/api/v1/users/me/therapy-requests')
            ->assertJsonPath('data.0.status', BookingRequestStatus::Rejected)
            ->assertJsonPath('data.0.can_cancel', false);
    }

    public function test_permissions_and_ownership(): void
    {
        $privateId = $this->submitPrivate($this->createPrivatePackage())->json('data.id');
        $therapy = $this->createTherapy();
        $therapyId = $this->asMember()->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK])->json('data.id');

        auth('api')->forgetUser();
        $receptionist = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Receptionist])));
        $receptionist->getJson('/api/v1/admin/private-requests?status=pending')->assertOk()->assertJsonCount(1, 'data');
        $receptionist->getJson('/api/v1/admin/therapy-requests')->assertOk()->assertJsonCount(1, 'data');
        $receptionist->patchJson("/api/v1/admin/private-requests/{$privateId}/approve")->assertForbidden();
        $receptionist->patchJson("/api/v1/admin/therapy-requests/{$therapyId}/approve")->assertForbidden();
        $receptionist->patchJson("/api/v1/admin/private-requests/{$privateId}", [])->assertForbidden();

        auth('api')->forgetUser();
        $trainer = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Trainer])));
        $trainer->getJson('/api/v1/admin/private-requests')->assertForbidden();

        $other = User::factory()->create();
        $this->asMember($other)->patchJson("/api/v1/private-requests/{$privateId}/cancel")->assertNotFound();
        $this->asMember($other)->patchJson("/api/v1/therapy-requests/{$therapyId}/cancel")->assertNotFound();
        $this->asMember($other)->postJson('/api/v1/therapy-requests', ['user_therapy_id' => $therapy->id, 'preferred_at' => self::NEXT_WEEK])->assertNotFound();
    }

    public function test_admin_member_lists_by_group_and_therapy(): void
    {
        $private = $this->createPrivatePackage();
        $this->createPrivatePackage(PackageType::ClassPack);
        $this->createTherapy();
        $id = $this->submitPrivate($private)->json('data.id');

        $admin = $this->asAdmin();
        $admin->getJson('/api/v1/admin/bookings/members?group=private')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $private->id);
        $admin->getJson('/api/v1/admin/bookings/members')->assertJsonCount(1, 'data')->assertJsonPath('data.0.package_type', PackageType::ClassPack);
        $admin->getJson('/api/v1/admin/bookings/therapy-members')->assertJsonPath('data.0.sessions_remaining', 5);
        $admin->getJson("/api/v1/admin/bookings/members/{$private->id}")->assertJsonPath('data.private_requests.0.id', $id);
    }
}
