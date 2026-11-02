<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\EventStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageType;
use App\Share\Enums\Role;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\Event;
use App\Share\Models\FitnessClass;
use App\Share\Models\Order;
use App\Share\Models\Package;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $trainer;

    private User $receptionist;

    private User $member;

    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->trainer = User::factory()->create(['role' => Role::Trainer, 'first_name' => 'Thu', 'last_name' => 'Hà']);
        $this->receptionist = User::factory()->create(['role' => Role::Receptionist]);
        $this->member = User::factory()->create();
        $this->package = Package::query()->create([
            'package_type' => PackageType::ClassPack,
            'price' => 1000000,
            'duration_days' => 90,
            'class_credits' => 10,
            'session_duration_minutes' => 60,
            'vi' => ['name' => '10 classes'],
        ]);
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

    private function createOrder(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'code' => 'GYM-'.uniqid(),
            'user_id' => $this->member->id,
            'type' => OrderType::Package,
            'package_id' => $this->package->id,
            'name' => '10 classes',
            'amount' => 1000000,
            'status' => OrderStatus::Success,
            'paid_at' => '2026-11-05 09:00:00',
        ], $overrides));
    }

    private function createSchedule(string $start, ?User $trainer = null, int $maxSlot = 4): ClassSchedule
    {
        $class = FitnessClass::query()->first() ?? FitnessClass::query()->create([
            'slug' => 'yoga-flow',
            'category' => FitnessClassCategory::YogaSound,
            'name' => 'Yoga Flow',
            'vi' => ['description' => 'Mô tả'],
        ]);

        return ClassSchedule::query()->create([
            'fitness_class_id' => $class->id,
            'trainer_id' => ($trainer ?? $this->trainer)->id,
            'room' => 'P101',
            'start_time' => $start,
            'end_time' => Carbon::parse($start)->addHour(),
            'max_slot' => $maxSlot,
            'status' => ClassScheduleStatus::Active,
        ]);
    }

    private function createBooking(ClassSchedule $schedule, string $status = BookingStatus::Confirmed, ?User $user = null): Booking
    {
        $user ??= $this->member;
        $order = $this->createOrder(['user_id' => $user->id, 'paid_at' => '2026-01-01 09:00:00']);
        $package = UserPackage::query()->create([
            'user_id' => $user->id,
            'package_id' => $this->package->id,
            'order_id' => $order->id,
            'name' => '10 classes',
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

        return Booking::query()->create([
            'user_id' => $user->id,
            'user_package_id' => $package->id,
            'class_schedule_id' => $schedule->id,
            'status' => $status,
        ]);
    }

    public function test_revenue_counts_only_paid_orders_in_range_with_snapshot_amount(): void
    {
        $this->createOrder();
        $this->createOrder(['amount' => 500000, 'paid_at' => '2026-11-06 23:30:00']);
        $this->createOrder(['paid_at' => '2026-09-01 09:00:00']);
        $this->createOrder(['status' => OrderStatus::Pending, 'paid_at' => null]);
        $this->createOrder(['status' => OrderStatus::Cancelled, 'paid_at' => null]);
        $this->package->update(['price' => 9000000]);

        $admin = $this->as($this->admin);
        $admin->getJson('/api/v1/admin/reports/overview?from=2026-11-01&to=2026-11-10')
            ->assertOk()
            ->assertJsonPath('data.total_revenue', 1500000)
            ->assertJsonPath('data.revenue_by_type.package', 1500000)
            ->assertJsonPath('data.revenue_by_type.event', 0);

        $response = $admin->getJson('/api/v1/admin/reports/revenue?from=2026-11-05&to=2026-11-07')->assertOk();
        $response->assertJsonCount(3, 'data.series');
        $response->assertJsonPath('data.series.0', ['date' => '2026-11-05', 'value' => 1000000]);
        $response->assertJsonPath('data.series.1', ['date' => '2026-11-06', 'value' => 500000]);
        $response->assertJsonPath('data.series.2', ['date' => '2026-11-07', 'value' => 0]);
    }

    public function test_breakdown_groups_by_product_and_counts_quantity(): void
    {
        $event = Event::query()->create([
            'event_date' => '2026-12-01 09:00:00',
            'location' => 'Sảnh',
            'ticket_price' => 100000,
            'total_tickets' => 50,
            'status' => EventStatus::Open,
            'vi' => ['name' => 'Workshop'],
        ]);
        $this->createOrder();
        $this->createOrder();
        $this->createOrder(['type' => OrderType::Event, 'package_id' => null, 'event_id' => $event->id, 'quantity' => 3, 'name' => 'Vé Workshop × 3', 'amount' => 300000]);
        $this->createOrder(['type' => OrderType::Event, 'package_id' => null, 'event_id' => $event->id, 'quantity' => 1, 'name' => 'Vé Workshop × 1', 'amount' => 100000]);
        $this->createOrder(['type' => OrderType::Redlight, 'package_id' => null, 'name' => 'Redlight — A', 'amount' => 30000]);
        $this->createOrder(['type' => OrderType::Redlight, 'package_id' => null, 'name' => 'Redlight — B', 'amount' => 30000]);

        $breakdown = collect($this->as($this->admin)->getJson('/api/v1/admin/reports/revenue?from=2026-11-01&to=2026-11-10')->assertOk()->json('data.breakdown'));

        $this->assertSame(['type' => 'package', 'name' => '10 classes', 'quantity_sold' => 2, 'revenue' => 2000000], $breakdown[0]);
        $this->assertSame(['type' => 'event', 'name' => 'Workshop', 'quantity_sold' => 4, 'revenue' => 400000], $breakdown[1]);
        $this->assertSame(['type' => 'redlight', 'name' => 'Redlight', 'quantity_sold' => 2, 'revenue' => 60000], $breakdown[2]);
    }

    public function test_new_member_is_first_package_purchase_in_range(): void
    {
        $newMember = User::factory()->create();
        $appOnly = User::factory()->create();
        $eventOnly = User::factory()->create();
        $this->createOrder(['paid_at' => '2026-09-01 09:00:00']);
        $this->createOrder();
        $this->createOrder(['user_id' => $newMember->id, 'paid_at' => '2026-11-06 09:00:00']);
        $this->createOrder(['user_id' => $eventOnly->id, 'type' => OrderType::Redlight, 'package_id' => null, 'amount' => 30000]);

        $response = $this->as($this->admin)->getJson('/api/v1/admin/reports/members?from=2026-11-05&to=2026-11-06')->assertOk();

        $response->assertJsonPath('data.new_members', 1);
        $response->assertJsonPath('data.buyers', 2);
        $response->assertJsonPath('data.renewal_rate', 50);
        $response->assertJsonPath('data.series.1', ['date' => '2026-11-06', 'value' => 1]);
        $this->assertNotNull($appOnly->id);
    }

    public function test_cancellation_rate_uses_bookings_created_in_range(): void
    {
        Carbon::setTestNow('2026-11-05 10:00:00');
        $schedule = $this->createSchedule('2026-11-20 10:00:00');
        $this->createBooking($schedule);
        $this->createBooking($schedule, BookingStatus::Confirmed, User::factory()->create());
        $this->createBooking($schedule, BookingStatus::Confirmed, User::factory()->create());
        $this->createBooking($schedule, BookingStatus::Cancelled, User::factory()->create());
        Carbon::setTestNow('2026-11-10 10:00:00');

        $this->as($this->admin)->getJson('/api/v1/admin/reports/overview?from=2026-11-01&to=2026-11-10')
            ->assertJsonPath('data.bookings', 4)
            ->assertJsonPath('data.cancellation_rate', 25);
        $this->as($this->admin)->getJson('/api/v1/admin/reports/overview?from=2026-11-06&to=2026-11-10')
            ->assertJsonPath('data.bookings', 0)
            ->assertJsonPath('data.cancellation_rate', 0);
    }

    public function test_class_stats_count_only_finished_active_sessions(): void
    {
        $other = User::factory()->create(['role' => Role::Trainer, 'first_name' => 'Minh', 'last_name' => 'Anh']);
        $full = $this->createSchedule('2026-11-05 09:00:00', maxSlot: 2);
        $this->createBooking($full);
        $this->createBooking($full, BookingStatus::Confirmed, User::factory()->create());
        $this->createBooking($full, BookingStatus::Cancelled, User::factory()->create());
        $this->createSchedule('2026-11-06 09:00:00', $other, 2);
        $this->createBooking($this->createSchedule('2026-11-20 09:00:00'));
        $cancelled = $this->createSchedule('2026-11-07 09:00:00');
        $cancelled->update(['status' => ClassScheduleStatus::Cancelled]);

        $response = $this->as($this->admin)->getJson('/api/v1/admin/reports/classes?from=2026-11-01&to=2026-11-30')->assertOk();

        $response->assertJsonPath('data.classes.0', ['name' => 'Yoga Flow', 'sessions' => 2, 'bookings' => 2, 'fill_rate_pct' => 50]);
        $response->assertJsonPath('data.trainers.0', ['name' => 'Thu Hà', 'sessions' => 1, 'bookings' => 2, 'fill_rate_pct' => 100]);
        $response->assertJsonPath('data.trainers.1', ['name' => 'Minh Anh', 'sessions' => 1, 'bookings' => 0, 'fill_rate_pct' => 0]);
    }

    public function test_range_validation_and_defaults(): void
    {
        $this->createOrder(['paid_at' => '2026-10-12 09:00:00']);
        $this->createOrder(['paid_at' => '2026-10-11 23:00:00']);
        $admin = $this->as($this->admin);

        $admin->getJson('/api/v1/admin/reports/overview')->assertOk()->assertJsonPath('data.total_revenue', 1000000);
        $admin->getJson('/api/v1/admin/reports/revenue')->assertOk()->assertJsonCount(30, 'data.series');
        $admin->getJson('/api/v1/admin/reports/overview?from=2026-11-10&to=2026-11-01')->assertUnprocessable();
        $admin->getJson('/api/v1/admin/reports/overview?from=2024-01-01&to=2026-11-01')->assertUnprocessable();
        $admin->getJson('/api/v1/admin/reports/overview?from=abc')->assertUnprocessable();
    }

    public function test_reports_are_admin_only(): void
    {
        foreach ([$this->receptionist, $this->trainer, $this->member] as $user) {
            $client = $this->as($user);
            foreach (['overview', 'revenue', 'members', 'classes'] as $report) {
                $client->getJson("/api/v1/admin/reports/{$report}")->assertForbidden();
            }
        }
    }

    public function test_dashboard_differs_by_role_and_hides_revenue_from_staff(): void
    {
        $this->createOrder();
        $own = $this->createSchedule('2026-11-10 18:00:00');
        $this->createBooking($own);
        $otherTrainer = User::factory()->create(['role' => Role::Trainer]);
        $this->createBooking($this->createSchedule('2026-11-10 19:00:00', $otherTrainer), BookingStatus::Confirmed, User::factory()->create());

        $admin = collect($this->as($this->admin)->getJson('/api/v1/admin/dashboard')->assertOk()->json('data'))->keyBy('label');
        $this->assertSame(1000000, $admin['Doanh thu']['value']);
        $this->assertSame('currency', $admin['Doanh thu']['format']);
        $this->assertSame(2, $admin['Lớp hôm nay']['value']);

        $trainer = collect($this->as($this->trainer)->getJson('/api/v1/admin/dashboard')->assertOk()->json('data'))->keyBy('label');
        $this->assertFalse($trainer->has('Doanh thu'));
        $this->assertSame(1, $trainer['Lớp hôm nay']['value']);
        $this->assertSame(1, $trainer['Học viên hôm nay']['value']);
        $this->assertSame(1, $trainer['Chưa điểm danh']['value']);

        $receptionist = collect($this->as($this->receptionist)->getJson('/api/v1/admin/dashboard')->assertOk()->json('data'))->keyBy('label');
        $this->assertFalse($receptionist->has('Doanh thu'));
        $this->assertSame(2, $receptionist['Học viên hôm nay']['value']);

        $this->as($this->member)->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }
}
