<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageType;
use App\Share\Enums\Role;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\FitnessClass;
use App\Share\Models\Order;
use App\Share\Models\Package;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RedlightFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $trainer;

    private User $member;

    private UserPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 08:00:00');
        config(['gym.payment_mock_enabled' => true]);
        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->trainer = User::factory()->create(['role' => Role::Trainer]);
        $this->member = User::factory()->create();
        $this->package = $this->createUserPackage($this->member);
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

    private function createUserPackage(User $user): UserPackage
    {
        $package = Package::query()->create([
            'package_type' => PackageType::ClassPack,
            'price' => 1000000,
            'duration_days' => 90,
            'class_credits' => 10,
            'session_duration_minutes' => 60,
            'vi' => ['name' => 'Gói test'],
        ]);
        $order = Order::query()->create([
            'code' => 'GYM-'.uniqid(),
            'user_id' => $user->id,
            'type' => OrderType::Package,
            'package_id' => $package->id,
            'name' => 'Gói test',
            'amount' => 1000000,
            'status' => OrderStatus::Success,
        ]);

        return UserPackage::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'order_id' => $order->id,
            'name' => 'Gói test',
            'package_type' => PackageType::ClassPack,
            'total_credits' => 10,
            'remaining_credits' => 5,
            'session_duration_minutes' => 60,
            'is_off_peak' => false,
            'base_people_max' => 2,
            'extra_people_session_max' => 2,
            'start_date' => '2026-11-01',
            'end_date' => '2027-01-31',
        ]);
    }

    private function createBooking(string $category = FitnessClassCategory::Barre, string $start = '2026-11-12 18:00:00', ?User $user = null, bool $hasRedlight = false): Booking
    {
        $class = FitnessClass::query()->firstOrCreate(['slug' => $category], [
            'category' => $category,
            'name' => 'Lớp '.$category,
            'vi' => ['description' => 'Mô tả'],
        ]);
        $schedule = ClassSchedule::query()->create([
            'fitness_class_id' => $class->id,
            'trainer_id' => $this->trainer->id,
            'room' => 'P101',
            'start_time' => $start,
            'end_time' => Carbon::parse($start)->addHour(),
            'max_slot' => 10,
            'status' => ClassScheduleStatus::Active,
        ]);

        return Booking::query()->create([
            'user_id' => ($user ?? $this->member)->id,
            'user_package_id' => $user ? $this->createUserPackage($user)->id : $this->package->id,
            'class_schedule_id' => $schedule->id,
            'status' => BookingStatus::Confirmed,
            'has_redlight' => $hasRedlight,
        ]);
    }

    private function buy(int $quantity)
    {
        $code = $this->as($this->member)->postJson('/api/v1/orders', ['type' => 'redlight', 'quantity' => $quantity])
            ->assertCreated()
            ->json('data.code');

        return $this->as($this->member)->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => 'momo'])->assertOk();
    }

    private function available(): int
    {
        return $this->as($this->member)->getJson('/api/v1/users/me/redlight')->assertOk()->json('data.available');
    }

    public function test_buys_credits_without_signing_terms(): void
    {
        $code = $this->as($this->member)->postJson('/api/v1/orders', ['type' => 'redlight', 'quantity' => 5])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.quantity', 5)
            ->assertJsonPath('data.amount', 150000)
            ->json('data.code');

        $this->assertSame(0, $this->available());

        $this->as($this->member)->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => 'momo'])->assertOk();

        $this->as($this->member)->getJson('/api/v1/users/me/redlight')
            ->assertJsonPath('data.purchased_total', 5)
            ->assertJsonPath('data.used', 0)
            ->assertJsonPath('data.available', 5)
            ->assertJsonPath('data.price_per_credit', 30000);
    }

    public function test_quantity_is_required(): void
    {
        $this->as($this->member)->postJson('/api/v1/orders', ['type' => 'redlight'])->assertUnprocessable();
    }

    public function test_book_with_redlight_uses_one_credit_for_any_class(): void
    {
        $this->buy(2);
        $yoga = $this->createBooking(FitnessClassCategory::YogaSound)->classSchedule;
        $sculpt = $this->createBooking(FitnessClassCategory::Sculpt, '2026-11-13 18:00:00')->classSchedule;
        Booking::query()->delete();

        $this->as($this->member)->postJson('/api/v1/bookings', ['class_schedule_id' => $yoga->id, 'user_package_id' => $this->package->id, 'use_redlight' => true])
            ->assertCreated()
            ->assertJsonPath('data.has_redlight', true)
            ->assertJsonPath('data.redlight_available', false)
            ->assertJsonPath('data.can_remove_redlight', true);
        $this->as($this->member)->postJson('/api/v1/bookings', ['class_schedule_id' => $sculpt->id, 'user_package_id' => $this->package->id])
            ->assertCreated()
            ->assertJsonPath('data.has_redlight', false)
            ->assertJsonPath('data.redlight_available', true);

        $this->assertSame(1, $this->available());
    }

    public function test_book_with_redlight_without_credit_is_rejected_and_nothing_booked(): void
    {
        $schedule = $this->createBooking()->classSchedule;
        Booking::query()->delete();

        $this->as($this->member)->postJson('/api/v1/bookings', ['class_schedule_id' => $schedule->id, 'user_package_id' => $this->package->id, 'use_redlight' => true])
            ->assertUnprocessable();

        $this->assertSame(0, Booking::query()->count());
        $this->assertSame(5, $this->package->refresh()->remaining_credits);
    }

    public function test_attach_and_detach_redlight_on_existing_booking(): void
    {
        $this->buy(1);
        $booking = $this->createBooking();
        $path = "/api/v1/bookings/{$booking->id}/redlight";

        $this->as($this->member)->postJson($path)->assertOk();
        $this->assertTrue($booking->refresh()->has_redlight);
        $this->assertSame(0, $this->available());

        $this->as($this->member)->postJson($path)->assertUnprocessable();

        $this->as($this->member)->deleteJson($path)->assertOk();
        $this->assertFalse($booking->refresh()->has_redlight);
        $this->assertSame(1, $this->available());

        $this->as($this->member)->deleteJson($path)->assertUnprocessable();
    }

    public function test_attach_without_credit_is_rejected(): void
    {
        $booking = $this->createBooking();

        $this->as($this->member)->postJson("/api/v1/bookings/{$booking->id}/redlight")->assertUnprocessable();
        $this->assertFalse($booking->refresh()->has_redlight);
    }

    public function test_cannot_attach_after_class_started_or_detach_within_lock_window(): void
    {
        $this->buy(2);
        $started = $this->createBooking(start: '2026-11-10 07:30:00');
        $this->as($this->member)->postJson("/api/v1/bookings/{$started->id}/redlight")->assertUnprocessable();

        $soon = $this->createBooking(start: '2026-11-10 12:00:00');
        $this->as($this->member)->postJson("/api/v1/bookings/{$soon->id}/redlight")->assertOk();
        $this->as($this->member)->getJson('/api/v1/users/me/bookings')->assertJsonPath('data.0.can_remove_redlight', false);
        $this->as($this->member)->deleteJson("/api/v1/bookings/{$soon->id}/redlight")->assertUnprocessable();
        $this->assertTrue($soon->refresh()->has_redlight);
    }

    public function test_other_members_booking_returns_not_found(): void
    {
        $this->buy(1);
        $other = $this->createBooking(user: User::factory()->create(), hasRedlight: true);

        $this->as($this->member)->postJson("/api/v1/bookings/{$other->id}/redlight")->assertNotFound();
        $this->as($this->member)->deleteJson("/api/v1/bookings/{$other->id}/redlight")->assertNotFound();
    }

    public function test_member_cancel_returns_credit(): void
    {
        $this->buy(1);
        $booking = $this->createBooking(hasRedlight: true);
        $this->assertSame(0, $this->available());

        $this->as($this->member)->patchJson("/api/v1/bookings/{$booking->id}/cancel")->assertOk();

        $this->assertSame(1, $this->available());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->member->id,
            'message' => __('messages.gym_notify_member_cancelled_redlight', ['name' => $booking->classSchedule->fitnessClass->name, 'time' => '18:00 12/11/2026']),
        ]);
        $this->as($this->member)->getJson('/api/v1/users/me/bookings')
            ->assertJsonPath('data.0.status', 'cancelled')
            ->assertJsonPath('data.0.has_redlight', true)
            ->assertJsonPath('data.0.redlight_available', false);
    }

    public function test_class_cancelled_by_staff_returns_credit(): void
    {
        $this->buy(1);
        $booking = $this->createBooking(hasRedlight: true);

        $this->as($this->admin)->patchJson("/api/v1/admin/timetable/{$booking->class_schedule_id}/cancel")->assertOk();

        $this->assertSame(1, $this->available());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->member->id,
            'message' => __('messages.gym_notify_booking_cancelled_redlight', ['name' => $booking->classSchedule->fitnessClass->name, 'time' => '18:00 12/11/2026']),
        ]);
    }

    public function test_attended_or_no_show_keeps_credit_used(): void
    {
        $this->buy(1);
        $this->createBooking(start: '2026-11-10 09:00:00', hasRedlight: true);

        Carbon::setTestNow('2026-11-10 11:00:00');

        $this->assertSame(0, $this->available());
    }

    public function test_staff_see_who_uses_redlight(): void
    {
        $booking = $this->createBooking(start: '2026-11-10 18:00:00', hasRedlight: true);

        $trainer = $this->as($this->trainer);
        $trainer->getJson('/api/v1/admin/attendance/sessions')->assertOk()->assertJsonPath('data.0.members.0.has_redlight', true);
        $trainer->getJson("/api/v1/admin/schedules/class/{$booking->class_schedule_id}")->assertOk()->assertJsonPath('data.students.0.has_redlight', true);
        $this->as($this->admin)->getJson("/api/v1/admin/bookings/members/{$this->package->id}")->assertOk()->assertJsonPath('data.bookings.0.has_redlight', true);
    }
}
