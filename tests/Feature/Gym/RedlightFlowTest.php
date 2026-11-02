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
use App\Share\Services\Gym\BookingService;
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

    private function createBooking(string $category = FitnessClassCategory::Barre, string $start = '2026-11-12 18:00:00', ?User $user = null): Booking
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
        ]);
    }

    private function order(Booking $booking)
    {
        return $this->as($this->member)->postJson('/api/v1/orders', ['type' => 'redlight', 'booking_id' => $booking->id]);
    }

    private function pay(string $code)
    {
        return $this->as($this->member)->postJson('/api/v1/payments', ['order_code' => $code, 'payment_method' => 'momo']);
    }

    public function test_buys_redlight_for_one_session_without_signing_terms(): void
    {
        $booking = $this->createBooking();
        $other = $this->createBooking(start: '2026-11-13 18:00:00');

        $code = $this->order($booking)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.amount', 30000)
            ->assertJsonPath('data.type', 'redlight')
            ->json('data.code');

        $this->as($this->member)->getJson('/api/v1/users/me/bookings')
            ->assertJsonPath('data.1.redlight_order_code', $code)
            ->assertJsonPath('data.1.has_redlight', false);

        $this->pay($code)->assertOk();

        $this->assertNotNull($booking->refresh()->redlight_paid_at);
        $this->assertNull($other->refresh()->redlight_paid_at);
        $this->as($this->member)->getJson('/api/v1/users/me/bookings')
            ->assertJsonPath('data.1.has_redlight', true)
            ->assertJsonPath('data.1.redlight_available', false)
            ->assertJsonPath('data.0.redlight_available', true);
        $this->assertSame(5, $this->package->refresh()->remaining_credits);
    }

    public function test_only_barre_and_pilates_classes(): void
    {
        $this->order($this->createBooking(FitnessClassCategory::CorePilates))->assertCreated();
        $this->order($this->createBooking(FitnessClassCategory::Barre, '2026-11-13 18:00:00'))->assertCreated();
        $this->order($this->createBooking(FitnessClassCategory::YogaSound))->assertUnprocessable();
        $this->order($this->createBooking(FitnessClassCategory::Sculpt))->assertUnprocessable();
    }

    public function test_schedule_list_shows_availability_by_category(): void
    {
        $barre = $this->createBooking()->classSchedule;
        $yoga = $this->createBooking(FitnessClassCategory::YogaSound)->classSchedule;

        $client = $this->as($this->member);
        $client->getJson("/api/v1/schedules/{$barre->id}")->assertOk()->assertJsonPath('data.redlight_available', true)->assertJsonPath('data.redlight_price', 30000);
        $client->getJson("/api/v1/schedules/{$yoga->id}")->assertOk()->assertJsonPath('data.redlight_available', false);
    }

    public function test_reuses_pending_order_and_blocks_after_paid(): void
    {
        $booking = $this->createBooking();

        $code = $this->order($booking)->json('data.code');
        $this->assertSame($code, $this->order($booking)->json('data.code'));
        $this->assertSame(1, Order::query()->where('booking_id', $booking->id)->count());

        $this->pay($code)->assertOk();
        $this->order($booking)->assertUnprocessable();
    }

    public function test_other_members_booking_returns_not_found(): void
    {
        $other = User::factory()->create();

        $this->order($this->createBooking(user: $other))->assertNotFound();
    }

    public function test_cannot_buy_or_pay_after_class_started(): void
    {
        $started = $this->createBooking(start: '2026-11-10 07:30:00');
        $this->order($started)->assertUnprocessable();

        $booking = $this->createBooking(start: '2026-11-10 09:00:00');
        $code = $this->order($booking)->assertCreated()->json('data.code');

        Carbon::setTestNow('2026-11-10 09:30:00');

        $this->pay($code)->assertUnprocessable();
        $this->assertNull($booking->refresh()->redlight_paid_at);
    }

    public function test_pending_order_cancelled_when_booking_cancelled_and_cannot_pay(): void
    {
        $booking = $this->createBooking();
        $code = $this->order($booking)->json('data.code');

        $this->as($this->member)->patchJson("/api/v1/bookings/{$booking->id}/cancel")->assertOk();

        $this->assertTrue(Order::query()->where('code', $code)->first()->status->is(OrderStatus::Cancelled));
        $this->pay($code)->assertUnprocessable();
    }

    public function test_pending_order_cancelled_when_class_finished(): void
    {
        $booking = $this->createBooking(start: '2026-11-10 09:00:00');
        $code = $this->order($booking)->json('data.code');

        Carbon::setTestNow('2026-11-10 11:00:00');
        app(BookingService::class)->deductFinishedBookings();

        $this->assertTrue(Order::query()->where('code', $code)->first()->status->is(OrderStatus::Cancelled));
    }

    public function test_paid_redlight_flags_refund_when_class_cancelled_and_order_stays_paid(): void
    {
        $booking = $this->createBooking();
        $code = $this->order($booking)->json('data.code');
        $this->pay($code)->assertOk();

        $admin = $this->as($this->admin);
        $admin->patchJson("/api/v1/admin/timetable/{$booking->class_schedule_id}/cancel")->assertOk();
        $admin->getJson("/api/v1/admin/bookings/members/{$this->package->id}")
            ->assertOk()
            ->assertJsonPath('data.bookings.0.needs_redlight_refund', true);

        $this->assertTrue(Order::query()->where('code', $code)->first()->status->is(OrderStatus::Success));
    }

    public function test_staff_see_who_uses_redlight(): void
    {
        $booking = $this->createBooking(start: '2026-11-10 18:00:00');
        $this->pay($this->order($booking)->json('data.code'))->assertOk();

        $trainer = $this->as($this->trainer);
        $trainer->getJson('/api/v1/admin/attendance/sessions')->assertOk()->assertJsonPath('data.0.members.0.has_redlight', true);
        $trainer->getJson("/api/v1/admin/schedules/class/{$booking->class_schedule_id}")->assertOk()->assertJsonPath('data.students.0.has_redlight', true);
    }
}
