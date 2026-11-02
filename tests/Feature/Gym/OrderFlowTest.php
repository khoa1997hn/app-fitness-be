<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageStatus;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TERMS = [
        'terms_accepted' => true,
        'signature_data' => 'data:image/png;base64,iVBORw0KGgo=',
        'buyer_name' => 'Phạm Thị Member',
        'buyer_phone' => '0900000004',
        'buyer_email' => 'member@example.com',
    ];

    private function createPackage(array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'package_type' => PackageType::ClassPack,
            'price' => 2100000,
            'duration_days' => 135,
            'class_credits' => 10,
            'session_duration_minutes' => 60,
            'vi' => ['name' => '10 classes', 'description' => 'Mô tả'],
        ], $overrides))->refresh();
    }

    private function createTherapy(): TherapyService
    {
        return TherapyService::query()->create([
            'slug' => 'deep-stretch',
            'single_price' => 500000,
            'single_duration_minutes' => 60,
            'combo5_price' => 2000000,
            'vi' => ['name' => 'Deep Stretch', 'long_description' => 'Mô tả', 'benefits' => ['Giãn cơ']],
        ]);
    }

    private function actingAsRole(string $role = Role::Member): static
    {
        return $this->actingAsUser(User::factory()->create(['role' => $role]));
    }

    private function actingAsUser(User $user): static
    {
        return $this->withToken(auth('api')->login($user));
    }

    private function createOrderFor(User $user, Package $package): Order
    {
        return Order::query()->create([
            'code' => 'GYM-TEST'.$user->id,
            'user_id' => $user->id,
            'type' => OrderType::Package,
            'package_id' => $package->id,
            'name' => $package->name,
            'amount' => $package->price,
            'status' => OrderStatus::PendingTerms,
        ]);
    }

    public function test_member_buys_package_through_full_flow(): void
    {
        $package = $this->createPackage();
        $client = $this->actingAsRole();

        $code = $client->postJson('/api/v1/orders', ['type' => OrderType::Package, 'package_id' => $package->id])
            ->assertCreated()
            ->assertJsonPath('data.status', OrderStatus::PendingTerms)
            ->assertJsonPath('data.amount', 2100000)
            ->assertJsonPath('data.is_created_by_staff', false)
            ->json('data.code');

        $client->postJson("/api/v1/orders/{$code}/accept-terms", self::TERMS)
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Pending);

        $client->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => PaymentMethod::Momo])
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::Success)
            ->assertJsonPath('data.payment_method', PaymentMethod::Momo);

        $client->getJson('/api/v1/users/me/packages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '10 classes')
            ->assertJsonPath('data.0.total_credits', 10)
            ->assertJsonPath('data.0.remaining_credits', 10)
            ->assertJsonPath('data.0.end_date', today()->addDays(135)->toDateString());

        $client->getJson('/api/v1/users/me/orders')->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.note');
    }

    public function test_member_buys_therapy_combo(): void
    {
        $therapy = $this->createTherapy();
        $client = $this->actingAsRole();

        $code = $client->postJson('/api/v1/orders', [
            'type' => OrderType::Therapy,
            'therapy_service_id' => $therapy->id,
            'therapy_variant' => TherapyVariant::Combo5,
        ])
            ->assertCreated()
            ->assertJsonPath('data.amount', 2000000)
            ->assertJsonPath('data.name', 'Deep Stretch — Combo 5 buổi')
            ->json('data.code');

        $client->postJson("/api/v1/orders/{$code}/accept-terms", self::TERMS)->assertOk();
        $client->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => PaymentMethod::Cash])->assertOk();

        $client->getJson('/api/v1/users/me/therapies')
            ->assertJsonPath('data.0.therapy_service_slug', 'deep-stretch')
            ->assertJsonPath('data.0.sessions_total', 5)
            ->assertJsonPath('data.0.sessions_remaining', 5)
            ->assertJsonPath('data.0.end_date', today()->addDays(90)->toDateString());
    }

    public function test_client_price_is_ignored(): void
    {
        $this->actingAsRole()
            ->postJson('/api/v1/orders', ['type' => OrderType::Package, 'package_id' => $this->createPackage()->id, 'amount' => 1, 'price' => 1])
            ->assertCreated()
            ->assertJsonPath('data.amount', 2100000);
    }

    public function test_hidden_package_cannot_be_ordered(): void
    {
        $package = $this->createPackage(['status' => PackageStatus::Hidden]);

        $this->actingAsRole()
            ->postJson('/api/v1/orders', ['type' => OrderType::Package, 'package_id' => $package->id])
            ->assertUnprocessable();
    }

    public function test_cannot_pay_before_signing(): void
    {
        $member = User::factory()->create();
        $order = $this->createOrderFor($member, $this->createPackage());

        $this->actingAsUser($member)
            ->postJson('/api/v1/payments', ['order_code' => $order->code, 'payment_method' => PaymentMethod::Momo])
            ->assertUnprocessable();

        $this->assertSame(0, UserPackage::query()->count());
    }

    public function test_paying_twice_grants_package_once(): void
    {
        $member = User::factory()->create();
        $order = $this->createOrderFor($member, $this->createPackage());
        $client = $this->actingAsUser($member);

        $client->postJson("/api/v1/orders/{$order->code}/accept-terms", self::TERMS)->assertOk();
        $body = ['order_code' => $order->code, 'payment_method' => PaymentMethod::Momo];
        $client->postJson('/api/v1/payments', $body)->assertOk();
        $client->postJson('/api/v1/payments', $body)->assertOk();

        $this->assertSame(1, UserPackage::query()->count());
    }

    public function test_cannot_sign_twice(): void
    {
        $member = User::factory()->create();
        $order = $this->createOrderFor($member, $this->createPackage());
        $client = $this->actingAsUser($member);

        $client->postJson("/api/v1/orders/{$order->code}/accept-terms", self::TERMS)->assertOk();
        $client->postJson("/api/v1/orders/{$order->code}/accept-terms", self::TERMS)->assertUnprocessable();
    }

    public function test_signature_must_be_png_data_url_and_terms_accepted(): void
    {
        $member = User::factory()->create();
        $order = $this->createOrderFor($member, $this->createPackage());

        $this->actingAsUser($member)
            ->postJson("/api/v1/orders/{$order->code}/accept-terms", [...self::TERMS, 'terms_accepted' => false, 'signature_data' => 'https://evil.example/x.png'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['terms_accepted', 'signature_data']);
    }

    public function test_other_member_cannot_touch_order(): void
    {
        $order = $this->createOrderFor(User::factory()->create(), $this->createPackage());
        $client = $this->actingAsRole();

        $client->getJson("/api/v1/orders/{$order->code}")->assertNotFound();
        $client->postJson("/api/v1/orders/{$order->code}/accept-terms", self::TERMS)->assertNotFound();
        $client->postJson('/api/v1/payments', ['order_code' => $order->code, 'payment_method' => PaymentMethod::Momo])->assertNotFound();
    }

    public function test_staff_cannot_use_member_endpoints(): void
    {
        $this->actingAsRole(Role::Receptionist)->getJson('/api/v1/users/me/packages')->assertForbidden();
    }

    public function test_payment_fails_when_mock_disabled(): void
    {
        config(['gym.payment_mock_enabled' => false]);
        $member = User::factory()->create();
        $order = $this->createOrderFor($member, $this->createPackage());
        $client = $this->actingAsUser($member);

        $client->postJson("/api/v1/orders/{$order->code}/accept-terms", self::TERMS)->assertOk();
        $client->postJson('/api/v1/payments', ['order_code' => $order->code, 'payment_method' => PaymentMethod::Momo])
            ->assertUnprocessable();

        $this->assertSame(0, UserPackage::query()->count());
    }

    #[DataProvider('counterRoles')]
    public function test_staff_creates_account_and_order_then_customer_signs_and_pays(string $role): void
    {
        $package = $this->createPackage();
        $staff = $this->actingAsRole($role);

        $memberId = $staff->postJson('/api/v1/admin/members', [
            'email' => 'khach@example.com',
            'password' => 'matkhau123',
            'first_name' => 'Nguyễn Văn',
            'last_name' => 'Khách',
            'phone' => '0900000009',
            'role' => Role::Admin,
        ])->assertCreated()->assertJsonPath('data.full_name', 'Nguyễn Văn Khách')->json('data.id');

        $this->assertSame(Role::Member, User::query()->find($memberId)->role->value);

        $code = $staff->postJson("/api/v1/admin/users/{$memberId}/orders", [
            'type' => OrderType::Package,
            'package_id' => $package->id,
            'note' => 'Khách tại quầy',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', OrderStatus::PendingTerms)
            ->assertJsonPath('data.is_created_by_staff', true)
            ->assertJsonPath('data.member.id', $memberId)
            ->assertJsonPath('data.note', 'Khách tại quầy')
            ->json('data.code');

        // Khách đăng nhập bằng mật khẩu nhân viên đã đặt, ký và thanh toán như đơn tự đặt
        auth('api')->forgetUser();
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'khach@example.com', 'password' => 'matkhau123'])
            ->assertOk()
            ->json('data.access_token');
        $customer = $this->withToken($token);

        $customer->getJson('/api/v1/users/me/orders')->assertJsonPath('data.0.code', $code);
        $customer->postJson("/api/v1/orders/{$code}/accept-terms", self::TERMS)->assertOk();
        $customer->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => PaymentMethod::Cash])->assertOk();
        $customer->getJson('/api/v1/users/me/packages')->assertJsonCount(1, 'data');
    }

    public static function counterRoles(): array
    {
        return [[Role::Admin], [Role::Receptionist], [Role::Trainer]];
    }

    public function test_member_cannot_use_staff_endpoints(): void
    {
        $target = User::factory()->create();
        $package = $this->createPackage();
        $client = $this->actingAsRole();

        $client->getJson('/api/v1/admin/members?q=a')->assertForbidden();
        $client->postJson('/api/v1/admin/members', [])->assertForbidden();
        $client->postJson("/api/v1/admin/users/{$target->id}/orders", ['type' => OrderType::Package, 'package_id' => $package->id])->assertForbidden();
        $client->getJson('/api/v1/admin/payments')->assertForbidden();
    }

    public function test_staff_cannot_create_order_for_staff_account(): void
    {
        $other = User::factory()->create(['role' => Role::Trainer]);

        $this->actingAsRole(Role::Receptionist)
            ->postJson("/api/v1/admin/users/{$other->id}/orders", ['type' => OrderType::Package, 'package_id' => $this->createPackage()->id])
            ->assertUnprocessable();
    }

    public function test_member_search_only_returns_members(): void
    {
        User::factory()->create(['first_name' => 'Tìm', 'last_name' => 'Thấy']);
        User::factory()->create(['first_name' => 'Tìm', 'last_name' => 'Nhân viên', 'role' => Role::Trainer]);

        $this->actingAsRole(Role::Receptionist)
            ->getJson('/api/v1/admin/members?q='.urlencode('Tìm Thấy'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.last_name', 'Thấy');
    }

    public function test_member_detail_hides_staff(): void
    {
        $staff = User::factory()->create(['role' => Role::Admin]);

        $this->actingAsRole(Role::Receptionist)->getJson("/api/v1/admin/members/{$staff->id}")->assertNotFound();
    }

    public function test_admin_payments_lists_detail_with_signature_and_filters(): void
    {
        $member = User::factory()->create(['email' => 'tim@example.com']);
        $order = $this->createOrderFor($member, $this->createPackage());
        $client = $this->actingAsUser($member);
        $client->postJson("/api/v1/orders/{$order->code}/accept-terms", self::TERMS)->assertOk();
        $client->getJson("/api/v1/orders/{$order->code}")->assertJsonMissingPath('data.terms_signature');

        auth('api')->forgetUser();
        $staff = $this->actingAsRole(Role::Trainer);
        $staff->getJson('/api/v1/admin/payments?status=pending&q=tim@')->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.terms_signature');
        $staff->getJson('/api/v1/admin/payments?status=success')->assertJsonCount(0, 'data');
        $staff->getJson("/api/v1/admin/payments/{$order->id}")->assertOk()->assertJsonPath('data.terms_signature', self::TERMS['signature_data']);
    }

    public function test_package_with_orders_cannot_be_deleted(): void
    {
        $package = $this->createPackage();
        $free = $this->createPackage(['vi' => ['name' => 'Chưa bán']]);
        $this->createOrderFor(User::factory()->create(), $package);
        $admin = $this->actingAsRole(Role::Admin);

        $admin->getJson("/api/v1/admin/packages/{$package->id}")->assertJsonPath('data.can_delete', false);
        $admin->getJson("/api/v1/admin/packages/{$free->id}")->assertJsonPath('data.can_delete', true);
        $admin->deleteJson("/api/v1/admin/packages/{$package->id}")->assertUnprocessable();
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_therapy_catalog_is_public(): void
    {
        $this->createTherapy();

        $this->getJson('/api/v1/therapy-services')->assertOk()->assertJsonPath('data.0.slug', 'deep-stretch')->assertJsonPath('data.0.combo5_sessions', 5);
        $this->getJson('/api/v1/therapy-services/deep-stretch')->assertOk()->assertJsonPath('data.benefits.0', 'Giãn cơ');
        $this->getJson('/api/v1/therapy-services/khong-co')->assertNotFound();
    }

    public function test_user_therapy_table_stays_empty_for_package_order(): void
    {
        $member = User::factory()->create();
        $order = $this->createOrderFor($member, $this->createPackage());
        $client = $this->actingAsUser($member);
        $client->postJson("/api/v1/orders/{$order->code}/accept-terms", self::TERMS)->assertOk();
        $client->postJson('/api/v1/payments', ['order_code' => $order->code, 'payment_method' => PaymentMethod::Momo])->assertOk();

        $this->assertSame(0, UserTherapy::query()->count());
    }
}
