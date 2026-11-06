<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageType;
use App\Share\Enums\PaymentMethod;
use App\Share\Enums\PrivateDiscipline;
use App\Share\Enums\Role;
use App\Share\Enums\UserStatus;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\FitnessClass;
use App\Share\Models\Order;
use App\Share\Models\Package;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;
use App\Share\Models\UserNotification;
use App\Share\Models\UserPackage;
use App\Share\Services\Gym\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserAccountFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $receptionist;

    private User $trainer;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->admin = User::factory()->create(['role' => Role::Admin, 'first_name' => 'Quản', 'last_name' => 'Trị']);
        $this->receptionist = User::factory()->create(['role' => Role::Receptionist]);
        $this->trainer = User::factory()->create(['role' => Role::Trainer]);
        $this->member = User::factory()->create(['first_name' => 'Hội', 'last_name' => 'Viên', 'password' => 'Secret@123']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function as(User $user): static
    {
        return $this->withToken(auth('api')->login($user));
    }

    private function createOrder(?User $user = null, string $status = OrderStatus::Success): Order
    {
        $package = Package::query()->create([
            'package_type' => PackageType::ClassPack,
            'price' => 1000000,
            'duration_days' => 90,
            'class_credits' => 10,
            'session_duration_minutes' => 60,
            'vi' => ['name' => 'Gói test'],
        ]);

        return Order::query()->create([
            'code' => 'GYM-'.uniqid(),
            'user_id' => ($user ?? $this->member)->id,
            'type' => OrderType::Package,
            'package_id' => $package->id,
            'name' => 'Gói test',
            'amount' => 1000000,
            'status' => $status,
        ]);
    }

    private function createUserPackage(array $overrides = [], string $type = PackageType::ClassPack): UserPackage
    {
        $order = $this->createOrder();

        return UserPackage::query()->create(array_merge([
            'user_id' => $this->member->id,
            'package_id' => $order->package_id,
            'order_id' => $order->id,
            'name' => 'Gói test',
            'package_type' => $type,
            'total_credits' => 10,
            'remaining_credits' => 5,
            'session_duration_minutes' => 60,
            'is_off_peak' => false,
            'base_people_max' => 2,
            'extra_people_session_max' => 2,
            'start_date' => '2026-08-01',
            'end_date' => '2027-01-31',
        ], $overrides));
    }

    private function createSchedule(string $start = '2026-11-10 18:00:00'): ClassSchedule
    {
        $class = FitnessClass::query()->first() ?? FitnessClass::query()->create([
            'slug' => 'yoga-flow',
            'category' => FitnessClassCategory::YogaSound,
            'name' => 'Yoga Flow',
            'vi' => ['description' => 'Mô tả'],
        ]);

        return ClassSchedule::query()->create([
            'fitness_class_id' => $class->id,
            'trainer_id' => $this->trainer->id,
            'room' => 'P101',
            'start_time' => $start,
            'end_time' => Carbon::parse($start)->addHour(),
            'max_slot' => 10,
            'status' => ClassScheduleStatus::Active,
        ]);
    }

    public function test_locked_user_cannot_login_or_use_existing_token_and_can_login_after_unlock(): void
    {
        $token = auth('api')->login($this->member);
        $credentials = ['email' => $this->member->email, 'password' => 'Secret@123'];

        $this->as($this->admin)->patchJson("/api/v1/admin/users/{$this->member->id}/status", ['status' => 'locked'])
            ->assertOk()->assertJsonPath('data.status', 'locked');

        auth('api')->forgetUser();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->postJson('/api/v1/auth/login', $credentials)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/profile')->assertUnauthorized();

        $this->member->update(['status' => UserStatus::Active]);
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->postJson('/api/v1/auth/login', $credentials)->assertOk();
    }

    public function test_new_registered_user_is_active(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'email' => 'moi@example.com',
            'password' => 'Secret@123',
            'first_name' => 'Mới',
            'last_name' => 'Đăng ký',
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'moi@example.com', 'status' => 'active', 'role' => 'member']);
    }

    public function test_user_list_filters_and_paginates(): void
    {
        $admin = $this->as($this->admin);

        $admin->getJson('/api/v1/admin/users')->assertOk()->assertJsonPath('data.meta.total', 4);
        $admin->getJson('/api/v1/admin/users?role=trainer')->assertOk()->assertJsonPath('data.items.0.id', $this->trainer->id);
        $admin->getJson('/api/v1/admin/users?q=Hội Viên')->assertOk()->assertJsonPath('data.meta.total', 1);
        $admin->getJson('/api/v1/admin/users?status=locked')->assertOk()->assertJsonPath('data.meta.total', 0);

        User::factory()->count(25)->create();
        $admin->getJson('/api/v1/admin/users?page=2')->assertOk()->assertJsonPath('data.meta.page', 2)->assertJsonPath('data.meta.last_page', 2);

        $this->as($this->member)->getJson('/api/v1/admin/users')->assertForbidden();
    }

    public function test_detail_returns_packages_orders_and_attendance(): void
    {
        $package = $this->createUserPackage(['end_date' => '2026-11-01']);
        $past = Booking::query()->create([
            'user_id' => $this->member->id,
            'user_package_id' => $package->id,
            'class_schedule_id' => $this->createSchedule('2026-11-09 18:00:00')->id,
            'status' => BookingStatus::Confirmed,
        ]);
        $past->attendanceRecord()->create(['status' => 'absent', 'attendance_date' => '2026-11-09']);
        Booking::query()->create([
            'user_id' => $this->member->id,
            'user_package_id' => $package->id,
            'class_schedule_id' => $this->createSchedule('2026-11-20 18:00:00')->id,
            'status' => BookingStatus::Confirmed,
        ]);

        $this->as($this->trainer)->getJson("/api/v1/admin/users/{$this->member->id}")
            ->assertOk()
            ->assertJsonPath('data.packages.0.can_extend', true)
            ->assertJsonCount(1, 'data.orders')
            ->assertJsonPath('data.attendance.absent_count', 1)
            ->assertJsonPath('data.attendance.unmarked_count', 0)
            ->assertJsonPath('data.attendance.absent_recent', 1)
            ->assertJsonCount(1, 'data.attendance.rows');
    }

    public function test_staff_can_only_edit_members_and_cannot_change_role(): void
    {
        $body = ['first_name' => 'Sửa', 'last_name' => 'Tên', 'phone' => '0911111111', 'gender' => 'female', 'email' => 'hack@example.com', 'role' => 'admin'];

        foreach ([$this->receptionist, $this->trainer] as $staff) {
            $client = $this->as($staff);
            $client->patchJson("/api/v1/admin/users/{$this->member->id}", $body)->assertOk()->assertJsonPath('data.gender', 'female');
            $client->patchJson("/api/v1/admin/users/{$this->admin->id}", $body)->assertForbidden();
            $client->patchJson("/api/v1/admin/users/{$this->admin->id}/status", ['status' => 'locked'])->assertForbidden();
            $client->postJson("/api/v1/admin/users/{$this->admin->id}/reset-password")->assertForbidden();
            $client->patchJson("/api/v1/admin/users/{$this->member->id}/role", ['role' => 'admin'])->assertForbidden();
            $client->postJson('/api/v1/admin/users', [
                'email' => 'nv@example.com', 'password' => 'Secret@123', 'first_name' => 'A', 'last_name' => 'B', 'phone' => '0900000000', 'role' => 'admin',
            ])->assertForbidden();
        }

        $member = $this->member->refresh();
        $this->assertNotSame('hack@example.com', $member->email);
        $this->assertTrue($member->role->is(Role::Member));
        $this->assertDatabaseMissing('users', ['email' => 'nv@example.com']);
    }

    public function test_admin_creates_staff_changes_role_but_not_own(): void
    {
        $admin = $this->as($this->admin);

        $id = $admin->postJson('/api/v1/admin/users', [
            'email' => 'hlv@example.com', 'password' => 'Secret@123', 'first_name' => 'H', 'last_name' => 'V', 'phone' => '0900000000', 'role' => 'trainer',
        ])->assertCreated()->assertJsonPath('data.status', 'active')->json('data.id');

        $admin->patchJson("/api/v1/admin/users/{$id}/role", ['role' => 'receptionist'])->assertOk()->assertJsonPath('data.role', 'receptionist');
        $admin->patchJson("/api/v1/admin/users/{$this->admin->id}/role", ['role' => 'member'])->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/users/{$this->admin->id}/status", ['status' => 'locked'])->assertUnprocessable();
    }

    public function test_admin_can_change_user_email_with_unique_check(): void
    {
        $admin = $this->as($this->admin);
        $body = ['first_name' => 'Hội', 'last_name' => 'Viên', 'phone' => '0911111111'];
        $this->member->update(['email_verified_at' => null]);

        $admin->patchJson("/api/v1/admin/users/{$this->member->id}", [...$body, 'email' => $this->receptionist->email])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $admin->patchJson("/api/v1/admin/users/{$this->member->id}", [...$body, 'email' => 'khong-hop-le'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $admin->patchJson("/api/v1/admin/users/{$this->member->id}", [...$body, 'email' => $this->member->email])->assertOk();

        $admin->patchJson("/api/v1/admin/users/{$this->member->id}", [...$body, 'email' => 'moi@example.com'])
            ->assertOk()->assertJsonPath('data.email', 'moi@example.com');
        $this->assertNotNull($this->member->refresh()->email_verified_at);

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->postJson('/api/v1/auth/login', ['email' => 'moi@example.com', 'password' => 'Secret@123'])->assertOk();
    }

    public function test_receptionist_can_create_member_and_reset_password_by_email(): void
    {
        Mail::fake();
        $client = $this->as($this->receptionist);

        $client->postJson('/api/v1/admin/users', [
            'email' => 'khach@example.com', 'password' => 'Secret@123', 'first_name' => 'K', 'last_name' => 'H', 'phone' => '0900000000', 'role' => 'member',
        ])->assertCreated();

        $oldHash = $this->member->password;
        $client->postJson("/api/v1/admin/users/{$this->member->id}/reset-password")->assertOk();
        $this->assertSame($oldHash, $this->member->refresh()->password);
        Mail::assertSent(\App\Share\Mail\ForgotPasswordMail::class);
    }

    public function test_delete_blocked_when_user_has_gym_data_or_schedules(): void
    {
        $this->createOrder(status: OrderStatus::PendingTerms);
        $this->createSchedule();
        $empty = User::factory()->create();
        $admin = $this->as($this->admin);

        $admin->deleteJson("/api/v1/admin/users/{$this->member->id}")->assertUnprocessable();
        $admin->deleteJson("/api/v1/admin/users/{$this->trainer->id}")->assertUnprocessable();
        $admin->deleteJson("/api/v1/admin/users/{$this->admin->id}")->assertUnprocessable();
        $admin->deleteJson("/api/v1/admin/users/{$empty->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $empty->id]);

        $this->as($this->receptionist)->deleteJson("/api/v1/admin/users/{$this->member->id}")->assertForbidden();
    }

    public function test_self_delete_blocked_with_gym_data_and_allowed_without(): void
    {
        $this->createOrder();
        $this->as($this->member)->deleteJson('/api/v1/auth/me')->assertUnprocessable();
        $this->assertNotSoftDeleted('users', ['id' => $this->member->id]);

        $free = User::factory()->create();
        $this->as($free)->deleteJson('/api/v1/auth/me')->assertOk();
        $this->assertSoftDeleted('users', ['id' => $free->id]);
    }

    public function test_extend_rules_and_notification(): void
    {
        $expired = $this->createUserPackage(['end_date' => '2026-11-01']);
        $active = $this->createUserPackage();
        $used = $this->createUserPackage(['end_date' => '2026-11-01', 'remaining_credits' => 0]);
        $url = "/api/v1/admin/users/{$this->member->id}/packages";
        $admin = $this->as($this->admin);

        $admin->patchJson("{$url}/{$expired->id}/extend", ['extra_days' => 15])
            ->assertOk()->assertJsonPath('data.end_date', '2026-11-16')->assertJsonPath('data.remaining', 5);
        $admin->patchJson("{$url}/{$active->id}/extend", ['extra_days' => 15])->assertUnprocessable();
        $admin->patchJson("{$url}/{$used->id}/extend", ['extra_days' => 15])->assertUnprocessable();
        $admin->patchJson("{$url}/{$expired->id}/extend", ['extra_days' => 0])->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/users/{$this->trainer->id}/packages/{$expired->id}/extend", ['extra_days' => 1])->assertNotFound();

        $this->as($this->receptionist)->patchJson("{$url}/{$expired->id}/extend", ['extra_days' => 1])->assertForbidden();
        $this->assertDatabaseHas('notifications', ['user_id' => $this->member->id, 'type' => 'package']);
    }

    public function test_member_updates_profile_and_changes_password(): void
    {
        $client = $this->as($this->member);

        $client->patchJson('/api/v1/users/me', [
            'first_name' => 'Tên', 'last_name' => 'Mới', 'phone' => '0922222222', 'dob' => '1995-02-03', 'gender' => 'male', 'role' => 'admin', 'email' => 'x@example.com',
        ])->assertOk()->assertJsonPath('data.gender', 'male')->assertJsonPath('data.dob', '1995-02-03');

        $this->assertTrue($this->member->refresh()->role->is(Role::Member));
        $this->assertNotSame('x@example.com', $this->member->email);

        $client->patchJson('/api/v1/users/me/password', ['current_password' => 'Sai@1234', 'password' => 'NewPass@123', 'password_confirmation' => 'NewPass@123'])->assertUnprocessable();
        $client->patchJson('/api/v1/users/me/password', ['current_password' => 'Secret@123', 'password' => 'NewPass@123', 'password_confirmation' => 'NewPass@123'])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->flushHeaders();
        $this->postJson('/api/v1/auth/login', ['email' => $this->member->email, 'password' => 'NewPass@123'])->assertOk();

        $this->as($this->trainer)->patchJson('/api/v1/users/me', ['first_name' => 'A', 'last_name' => 'B', 'phone' => '1'])->assertForbidden();
    }

    public function test_events_create_notifications_for_member_only(): void
    {
        $schedule = $this->createSchedule();
        $package = $this->createUserPackage();
        Booking::query()->create([
            'user_id' => $this->member->id,
            'user_package_id' => $package->id,
            'class_schedule_id' => $schedule->id,
            'status' => BookingStatus::Confirmed,
        ]);
        $private = PrivateRequest::query()->create([
            'user_id' => $this->member->id,
            'user_package_id' => $this->createUserPackage(type: PackageType::PrivatePack)->id,
            'discipline' => PrivateDiscipline::Yoga,
            'requested_at' => '2026-11-12 15:00:00',
            'people_count' => 1,
            'extra_people_count' => 0,
            'trainer_id' => $this->trainer->id,
            'status' => 'pending',
        ]);
        $order = $this->createOrder(status: OrderStatus::Pending);

        $admin = $this->as($this->admin);
        $admin->patchJson("/api/v1/admin/timetable/{$schedule->id}/cancel")->assertOk();
        $admin->patchJson("/api/v1/admin/private-requests/{$private->id}/approve")->assertOk();

        $this->as($this->member)->postJson('/api/v1/payments', ['order_code' => $order->code, 'payment_method' => PaymentMethod::getValues()[0]])->assertOk();

        $messages = UserNotification::query()->where('user_id', $this->member->id)->pluck('message');
        $this->assertCount(3, $messages);
        $this->assertTrue($messages->contains(fn (string $message) => str_contains($message, 'Yoga Flow') && str_contains($message, 'không bị trừ buổi')));
        $this->assertTrue($messages->contains(fn (string $message) => str_contains($message, 'Private Yoga') && str_contains($message, 'đã được duyệt')));
        $this->assertTrue($messages->contains(fn (string $message) => str_contains($message, 'Thanh toán thành công')));
    }

    public function test_notifications_are_private_and_can_be_marked_read(): void
    {
        $mine = UserNotification::query()->create(['user_id' => $this->member->id, 'type' => 'payment', 'message' => 'Của tôi']);
        $other = UserNotification::query()->create(['user_id' => User::factory()->create()->id, 'type' => 'payment', 'message' => 'Của người khác']);

        $client = $this->as($this->member);
        $client->getJson('/api/v1/users/me/notifications')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.read_at', null);
        $client->patchJson("/api/v1/notifications/{$other->id}/read")->assertNotFound();
        $client->patchJson("/api/v1/notifications/{$mine->id}/read")->assertOk();

        $this->assertNotNull($mine->refresh()->read_at);
        $this->assertNull($other->refresh()->read_at);
    }

    public function test_reminders_are_sent_once(): void
    {
        $package = $this->createUserPackage(['end_date' => '2026-11-15']);
        $this->createUserPackage(['end_date' => '2026-11-15', 'remaining_credits' => 0]);
        $this->createUserPackage();
        Booking::query()->create([
            'user_id' => $this->member->id,
            'user_package_id' => $package->id,
            'class_schedule_id' => $this->createSchedule('2026-11-11 08:00:00')->id,
            'status' => BookingStatus::Confirmed,
        ]);
        Booking::query()->create([
            'user_id' => $this->member->id,
            'user_package_id' => $package->id,
            'class_schedule_id' => $this->createSchedule('2026-11-14 08:00:00')->id,
            'status' => BookingStatus::Confirmed,
        ]);

        $service = app(NotificationService::class);

        $this->assertSame(2, $service->sendReminders());
        $this->assertSame(0, $service->sendReminders());
        $this->assertDatabaseCount('notifications', 2);
    }
}
