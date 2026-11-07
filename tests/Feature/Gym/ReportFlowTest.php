<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\AttendanceStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\EventStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageType;
use App\Share\Enums\Role;
use App\Share\Models\AttendanceRecord;
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

        $response->assertJsonPath('data.classes.0', ['name' => 'Yoga Flow', 'sessions' => 2, 'bookings' => 2, 'attended' => 0, 'fill_rate_pct' => 50]);
        $response->assertJsonPath('data.trainers.0', ['name' => 'Thu Hà', 'sessions' => 1, 'bookings' => 2, 'attended' => 0, 'fill_rate_pct' => 100]);
        $response->assertJsonPath('data.trainers.1', ['name' => 'Minh Anh', 'sessions' => 1, 'bookings' => 0, 'attended' => 0, 'fill_rate_pct' => 0]);
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

    public function test_dashboard_is_admin_only_and_filters_cards_by_date(): void
    {
        $this->createOrder();
        $this->createOrder(['paid_at' => '2026-10-01 12:00:00', 'amount' => 500000]);
        $this->createSchedule('2026-11-10 18:00:00');

        $cards = collect($this->as($this->admin)->getJson('/api/v1/admin/dashboard?from=2026-11-01&to=2026-11-10')->assertOk()->json('data'))->keyBy('label');
        $this->assertSame(1000000, $cards['Doanh thu']['value']);
        $this->assertSame('currency', $cards['Doanh thu']['format']);
        $this->assertSame(1, $cards['Buổi học trong kỳ']['value']);
        $this->assertSame('2026-11-01 — 2026-11-10', $cards['Doanh thu']['note']);
        $previous = collect($this->as($this->admin)->getJson('/api/v1/admin/dashboard?from=2026-10-01&to=2026-10-01')->assertOk()->json('data'))->keyBy('label');
        $this->assertSame(500000, $previous['Doanh thu']['value']);
        $this->assertSame(0, $previous['Buổi học trong kỳ']['value']);
        $this->as($this->admin)->getJson('/api/v1/admin/dashboard?from=2026-11-10&to=2026-11-01')->assertUnprocessable();

        foreach ([$this->trainer, $this->receptionist, $this->member] as $user) {
            $this->as($user)->getJson('/api/v1/admin/dashboard')->assertForbidden();
            $this->as($user)->getJson('/api/v1/admin/dashboard?from=invalid')->assertForbidden();
        }
    }

    public function test_cancellations_are_separated_by_actor_without_reclassifying_unknown_history(): void
    {
        $schedule = $this->createSchedule('2026-11-20 10:00:00');
        $memberCancelled = $this->createBooking($schedule, BookingStatus::Cancelled);
        $memberCancelled->update(['cancelled_by' => $memberCancelled->user_id]);
        $staffCancelled = $this->createBooking($schedule, BookingStatus::Cancelled, User::factory()->create());
        $staffCancelled->update(['cancelled_by' => $this->admin->id]);
        $this->createBooking($schedule, BookingStatus::Confirmed, User::factory()->create());
        $this->createBooking($schedule, BookingStatus::Cancelled, User::factory()->create());

        foreach (['overview', 'members'] as $report) {
            $this->as($this->admin)->getJson('/api/v1/admin/reports/'.$report.'?from=2026-11-10&to=2026-11-10')
                ->assertOk()
                ->assertJsonPath('data.cancellation_rate', 75)
                ->assertJsonPath('data.member_cancellations', 1)
                ->assertJsonPath('data.staff_cancellations', 1)
                ->assertJsonPath('data.member_cancellation_rate', 25)
                ->assertJsonPath('data.staff_cancellation_rate', 25);
        }
        $this->as($this->admin)->getJson('/api/v1/admin/reports/overview?from=2026-10-01&to=2026-10-01')
            ->assertJsonPath('data.member_cancellations', 0)
            ->assertJsonPath('data.staff_cancellation_rate', 0);
    }

    public function test_performance_groups_finished_classes_and_counts_only_present_confirmed_bookings(): void
    {
        $schedule = $this->createSchedule('2026-11-05 09:00:00', maxSlot: 4);
        $present = $this->createBooking($schedule);
        $absent = $this->createBooking($schedule, BookingStatus::Confirmed, User::factory()->create());
        $cancelled = $this->createBooking($schedule, BookingStatus::Cancelled, User::factory()->create());
        foreach ([[$present, AttendanceStatus::Present], [$absent, AttendanceStatus::Absent], [$cancelled, AttendanceStatus::Present]] as [$booking, $status]) {
            AttendanceRecord::query()->create(['booking_id' => $booking->id, 'status' => $status, 'attendance_date' => '2026-11-05', 'marked_by' => $this->admin->id]);
        }
        $this->createSchedule('2026-11-02 07:00:00', maxSlot: 2);
        $this->createSchedule('2026-11-20 09:00:00');
        $this->createSchedule('2026-10-31 09:00:00');
        $this->createSchedule('2026-11-07 09:00:00')->update(['status' => ClassScheduleStatus::Cancelled]);
        $data = $this->as($this->admin)->getJson('/api/v1/admin/reports/classes?from=2026-11-01&to=2026-11-30')->assertOk();
        $data->assertJsonPath('data.subjects.0.sessions', 2)
            ->assertJsonPath('data.subjects.0.bookings', 2)
            ->assertJsonPath('data.subjects.0.attended', 1)
            ->assertJsonPath('data.subjects.0.fill_rate_pct', 25)
            ->assertJsonCount(2, 'data.time_slots')
            ->assertJsonPath('data.time_slots.0.name', '07:00–08:00')
            ->assertJsonPath('data.time_slots.1.attended', 1)
            ->assertJsonPath('data.dates.0.name', '2026-11-02')
            ->assertJsonPath('data.dates.1.name', '2026-11-05')
            ->assertJsonPath('data.weekdays.0.name', '1')
            ->assertJsonPath('data.weekdays.1.name', '4');
    }
}
