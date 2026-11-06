<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageType;
use App\Share\Enums\Role;
use App\Share\Mail\GymNoticeMail;
use App\Share\Mail\ScheduleCancelledMail;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\FitnessClass;
use App\Share\Models\Order;
use App\Share\Models\Package;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $trainer;

    private FitnessClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->member = User::factory()->create();
        $this->trainer = User::factory()->create(['role' => Role::Trainer, 'first_name' => 'Thu', 'last_name' => 'Hà']);
        $this->class = FitnessClass::query()->create([
            'slug' => 'yoga-flow',
            'category' => FitnessClassCategory::YogaSound,
            'name' => 'Yoga Flow',
            'vi' => ['description' => 'Mô tả'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createSchedule(string $start = '2026-11-12 09:00:00', array $overrides = []): ClassSchedule
    {
        return ClassSchedule::query()->create(array_merge([
            'fitness_class_id' => $this->class->id,
            'trainer_id' => $this->trainer->id,
            'room' => 'P101',
            'start_time' => $start,
            'end_time' => Carbon::parse($start)->addHour(),
            'max_slot' => 15,
            'status' => ClassScheduleStatus::Active,
        ], $overrides));
    }

    private function createOwnedPackage(?User $user = null, array $overrides = []): UserPackage
    {
        $user ??= $this->member;
        $package = Package::query()->create([
            'package_type' => PackageType::ClassPack,
            'price' => 1000000,
            'duration_days' => 90,
            'class_credits' => 10,
            'session_duration_minutes' => 60,
            'vi' => ['name' => '10 classes'],
        ]);
        $order = Order::query()->create([
            'code' => 'GYM-'.uniqid(),
            'user_id' => $user->id,
            'type' => OrderType::Package,
            'package_id' => $package->id,
            'name' => '10 classes',
            'amount' => 1000000,
            'status' => OrderStatus::Success,
        ]);

        return UserPackage::query()->create(array_merge([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'order_id' => $order->id,
            'name' => '10 classes',
            'package_type' => PackageType::ClassPack,
            'total_credits' => 10,
            'remaining_credits' => 10,
            'session_duration_minutes' => 60,
            'is_off_peak' => false,
            'base_people_max' => 2,
            'extra_people_session_max' => 100,
            'start_date' => '2026-11-01',
            'end_date' => '2027-02-01',
        ], $overrides));
    }

    private function asMember(?User $user = null): static
    {
        return $this->withToken(auth('api')->login($user ?? $this->member));
    }

    private function book(ClassSchedule $schedule, UserPackage $package)
    {
        return $this->asMember()->postJson('/api/v1/bookings', [
            'class_schedule_id' => $schedule->id,
            'user_package_id' => $package->id,
        ]);
    }

    public function test_member_booking_deducts_credit_immediately(): void
    {
        $package = $this->createOwnedPackage();

        $this->book($this->createSchedule(), $package)
            ->assertCreated()
            ->assertJsonPath('data.class_name', 'Yoga Flow')
            ->assertJsonPath('data.trainer_name', 'Thu Hà')
            ->assertJsonPath('data.status', 'booked')
            ->assertJsonPath('data.can_cancel', true);

        $this->assertSame(9, $package->refresh()->remaining_credits);
    }

    public function test_cancel_at_least_8_hours_before_refunds_credit_once(): void
    {
        $package = $this->createOwnedPackage();
        $id = $this->book($this->createSchedule('2026-11-10 18:00:00'), $package)->json('data.id');

        $this->asMember()->patchJson("/api/v1/bookings/{$id}/cancel")->assertOk();
        $this->asMember()->patchJson("/api/v1/bookings/{$id}/cancel")->assertUnprocessable();

        $booking = Booking::query()->find($id);
        $this->assertSame(BookingStatus::Cancelled, $booking->status->value);
        $this->assertNull($booking->credit_deducted_at);
        $this->assertSame(10, $package->refresh()->remaining_credits);
    }

    public function test_cancel_within_8_hours_is_blocked_and_credit_stays_deducted(): void
    {
        $package = $this->createOwnedPackage();
        $id = $this->book($this->createSchedule('2026-11-10 17:59:00'), $package)
            ->assertJsonPath('data.status', 'locked')
            ->assertJsonPath('data.can_cancel', false)
            ->json('data.id');

        $this->asMember()->patchJson("/api/v1/bookings/{$id}/cancel")->assertUnprocessable();
        $this->assertSame(BookingStatus::Confirmed, Booking::query()->find($id)->status->value);
        $this->assertSame(9, $package->refresh()->remaining_credits);
    }

    public function test_no_show_is_not_deducted_again_after_class(): void
    {
        $package = $this->createOwnedPackage();
        $this->book($this->createSchedule(), $package)->assertCreated();

        Carbon::setTestNow('2026-11-12 10:00:00');

        $this->assertSame(9, $package->refresh()->remaining_credits);
    }

    public function test_unlimited_package_has_no_credit_change(): void
    {
        $package = $this->createOwnedPackage(overrides: ['package_type' => PackageType::Unlimited, 'total_credits' => null, 'remaining_credits' => null]);
        $id = $this->book($this->createSchedule(), $package)->assertCreated()->json('data.id');
        $this->assertNull($package->refresh()->remaining_credits);

        $this->asMember()->patchJson("/api/v1/bookings/{$id}/cancel")->assertOk();
        $this->book($this->createSchedule('2026-11-13 09:00:00'), $package)->assertCreated();

        $this->assertNull($package->refresh()->remaining_credits);
        $this->assertNotNull(Booking::query()->latest('id')->first()->credit_deducted_at);
    }

    public function test_cannot_book_more_than_remaining_credits(): void
    {
        $package = $this->createOwnedPackage(overrides: ['total_credits' => 1, 'remaining_credits' => 1]);

        $this->book($this->createSchedule('2026-11-12 09:00:00'), $package)->assertCreated();
        $this->book($this->createSchedule('2026-11-13 09:00:00'), $package)->assertUnprocessable();
    }

    public function test_cancelled_booking_frees_credit_and_seat(): void
    {
        $package = $this->createOwnedPackage(overrides: ['total_credits' => 1, 'remaining_credits' => 1]);
        $schedule = $this->createSchedule('2026-11-12 09:00:00', ['max_slot' => 1]);
        $id = $this->book($schedule, $package)->json('data.id');

        $this->asMember()->patchJson("/api/v1/bookings/{$id}/cancel")->assertOk();
        $this->book($schedule, $package)->assertCreated();
    }

    public function test_cannot_book_full_class_or_twice(): void
    {
        $package = $this->createOwnedPackage();
        $schedule = $this->createSchedule(overrides: ['max_slot' => 1]);

        $this->book($schedule, $package)->assertCreated();
        $this->book($schedule, $package)->assertUnprocessable();

        auth('api')->forgetUser();
        $other = User::factory()->create();
        $this->asMember($other)->postJson('/api/v1/bookings', [
            'class_schedule_id' => $schedule->id,
            'user_package_id' => $this->createOwnedPackage($other)->id,
        ])->assertUnprocessable();
    }

    public function test_cannot_book_past_cancelled_or_after_package_end(): void
    {
        $package = $this->createOwnedPackage();

        $this->book($this->createSchedule('2026-11-10 09:00:00'), $package)->assertUnprocessable();
        $this->book($this->createSchedule(overrides: ['status' => ClassScheduleStatus::Cancelled]), $package)->assertUnprocessable();
        $this->book($this->createSchedule('2027-02-02 09:00:00'), $package)->assertUnprocessable();
    }

    public function test_cannot_book_with_private_package_or_someone_elses(): void
    {
        $schedule = $this->createSchedule();

        $this->book($schedule, $this->createOwnedPackage(overrides: ['package_type' => PackageType::PrivatePack]))->assertUnprocessable();
        $this->book($schedule, $this->createOwnedPackage(User::factory()->create()))->assertNotFound();
    }

    #[DataProvider('offPeakTimes')]
    public function test_off_peak_window(string $time, bool $allowed): void
    {
        $package = $this->createOwnedPackage(overrides: ['is_off_peak' => true]);
        $schedule = $this->createSchedule("2026-11-12 {$time}:00");

        $this->book($schedule, $package)->assertStatus($allowed ? 201 : 422);

        $this->asMember()
            ->getJson("/api/v1/schedules?user_package_id={$package->id}&date=2026-11-12")
            ->assertJsonCount($allowed ? 1 : 0, 'data');
    }

    public static function offPeakTimes(): array
    {
        return [
            ['07:00', true],
            ['08:00', true],
            ['08:01', false],
            ['18:00', false],
            ['22:59', false],
            ['23:00', true],
        ];
    }

    public function test_regular_package_sees_all_hours(): void
    {
        $package = $this->createOwnedPackage();
        $this->createSchedule('2026-11-12 07:00:00');
        $this->createSchedule('2026-11-12 18:00:00');

        $this->asMember()
            ->getJson("/api/v1/schedules?user_package_id={$package->id}&date=2026-11-12&category=yoga-sound")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.class.description', 'Mô tả')
            ->assertJsonPath('data.0.is_booked', false);
    }

    public function test_schedule_list_shows_booked_and_full_flags(): void
    {
        $package = $this->createOwnedPackage();
        $schedule = $this->createSchedule(overrides: ['max_slot' => 1]);
        $this->book($schedule, $package)->assertCreated();

        $this->asMember()->getJson("/api/v1/schedules/{$schedule->id}")
            ->assertJsonPath('data.is_booked', true)
            ->assertJsonPath('data.is_full', true)
            ->assertJsonPath('data.booked_count', 1);
    }

    public function test_other_member_cannot_cancel_booking(): void
    {
        $id = $this->book($this->createSchedule(), $this->createOwnedPackage())->json('data.id');

        auth('api')->forgetUser();
        $this->asMember(User::factory()->create())->patchJson("/api/v1/bookings/{$id}/cancel")->assertNotFound();
    }

    public function test_my_bookings_only_lists_active_with_completed_status(): void
    {
        $package = $this->createOwnedPackage();
        $id = $this->book($this->createSchedule('2026-11-12 09:00:00'), $package)->json('data.id');
        $this->book($this->createSchedule('2026-11-13 09:00:00'), $package)->assertCreated();
        $this->asMember()->patchJson("/api/v1/bookings/{$id}/cancel")->assertOk();

        Carbon::setTestNow('2026-11-13 11:00:00');
        $this->asMember()->getJson('/api/v1/users/me/bookings')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.can_cancel', false)
            ->assertJsonPath('data.1.status', 'cancelled')
            ->assertJsonPath('data.1.can_cancel', false)
            ->assertJsonPath('data.1.cancelled_by_staff', false);
    }

    public function test_admin_cancel_refunds_credit_even_within_8_hours(): void
    {
        $package = $this->createOwnedPackage();
        $id = $this->book($this->createSchedule('2026-11-10 12:00:00'), $package)->json('data.id');

        auth('api')->forgetUser();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->withToken(auth('api')->login($admin))->patchJson("/api/v1/admin/bookings/{$id}/cancel")->assertOk();

        $this->assertSame(10, $package->refresh()->remaining_credits);
        $this->assertSame($admin->id, Booking::query()->find($id)->cancelled_by);
    }

    public function test_admin_cancel_emails_member_and_keeps_booking_in_history(): void
    {
        Mail::fake();
        $package = $this->createOwnedPackage();
        $id = $this->book($this->createSchedule('2026-11-10 12:00:00'), $package)->json('data.id');
        $member = $package->user;

        auth('api')->forgetUser();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->withToken(auth('api')->login($admin))->patchJson("/api/v1/admin/bookings/{$id}/cancel")->assertOk();

        Mail::assertQueued(ScheduleCancelledMail::class, fn (ScheduleCancelledMail $mail) => $mail->hasTo($member->email)
            && str_contains($mail->notice, 'Bạn không bị trừ buổi')
            && str_contains($mail->noticeEn, 'No session credit will be deducted'));
        $this->assertDatabaseHas('notifications', ['user_id' => $member->id, 'type' => 'schedule']);
        $this->assertSame(10, $package->refresh()->remaining_credits);

        auth('api')->forgetUser();
        $this->withToken(auth('api')->login($member))->getJson('/api/v1/users/me/bookings')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.status', 'cancelled')
            ->assertJsonPath('data.0.cancelled_by_staff', true);
    }

    public function test_cancelling_class_emails_every_booked_member(): void
    {
        Mail::fake();
        $schedule = $this->createSchedule('2026-11-10 12:00:00');
        $first = $this->createOwnedPackage();
        $this->book($schedule, $first)->assertCreated();
        $second = $this->createOwnedPackage(User::factory()->create(['role' => Role::Member]));
        Booking::query()->create(['user_id' => $second->user_id, 'class_schedule_id' => $schedule->id, 'user_package_id' => $second->id, 'status' => BookingStatus::Confirmed]);

        auth('api')->forgetUser();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->withToken(auth('api')->login($admin))->patchJson("/api/v1/admin/timetable/{$schedule->id}/cancel")->assertOk();

        Mail::assertQueued(ScheduleCancelledMail::class, 2);
        Mail::assertQueued(ScheduleCancelledMail::class, fn (ScheduleCancelledMail $mail) => $mail->hasTo($first->user->email));
        Mail::assertQueued(ScheduleCancelledMail::class, fn (ScheduleCancelledMail $mail) => $mail->hasTo($second->user->email));
    }

    public function test_member_cancel_does_not_send_email(): void
    {
        Mail::fake();
        $id = $this->book($this->createSchedule('2026-11-20 12:00:00'), $this->createOwnedPackage())->json('data.id');

        $this->patchJson("/api/v1/bookings/{$id}/cancel")->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_staff_cannot_cancel_after_class_finished(): void
    {
        $id = $this->book($this->createSchedule(), $this->createOwnedPackage())->json('data.id');
        Carbon::setTestNow('2026-11-12 11:00:00');

        auth('api')->forgetUser();
        $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])))
            ->patchJson("/api/v1/admin/bookings/{$id}/cancel")
            ->assertUnprocessable();
    }

    public function test_cancelling_class_cancels_bookings_and_refunds_credit(): void
    {
        $package = $this->createOwnedPackage();
        $schedule = $this->createSchedule();
        $id = $this->book($schedule, $package)->json('data.id');

        auth('api')->forgetUser();
        $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])))
            ->patchJson("/api/v1/admin/timetable/{$schedule->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', ClassScheduleStatus::Cancelled)
            ->assertJsonPath('data.booked_count', 0);

        $this->assertSame(BookingStatus::Cancelled, Booking::query()->find($id)->status->value);
        $this->assertSame(10, $package->refresh()->remaining_credits);
    }

    public function test_cancelling_finished_class_keeps_bookings_and_credit(): void
    {
        $package = $this->createOwnedPackage();
        $schedule = $this->createSchedule();
        $id = $this->book($schedule, $package)->json('data.id');
        Carbon::setTestNow('2026-11-12 11:00:00');

        auth('api')->forgetUser();
        $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])))
            ->patchJson("/api/v1/admin/timetable/{$schedule->id}/cancel")
            ->assertOk();

        $this->assertSame(BookingStatus::Confirmed, Booking::query()->find($id)->status->value);
        $this->assertSame(9, $package->refresh()->remaining_credits);
    }

    public function test_migration_deducts_credit_of_held_bookings(): void
    {
        $package = $this->createOwnedPackage();
        $held = $this->book($this->createSchedule(), $package)->json('data.id');
        Booking::query()->whereKey($held)->update(['credit_deducted_at' => null]);
        $package->refresh()->update(['remaining_credits' => 10]);

        (require database_path('migrations/2026_11_03_120000_deduct_credits_of_held_bookings.php'))->up();

        $this->assertSame(9, $package->refresh()->remaining_credits);
        $this->assertNotNull(Booking::query()->find($held)->credit_deducted_at);
    }

    public function test_trainer_is_emailed_when_assigned_to_class(): void
    {
        Mail::fake();
        $first = User::factory()->create(['role' => Role::Trainer]);
        $second = User::factory()->create(['role' => Role::Trainer]);
        $class = FitnessClass::query()->first() ?? $this->createSchedule()->fitnessClass;
        $body = [
            'fitness_class_id' => $class->id, 'trainer_id' => $first->id, 'room' => config('gym.rooms')[0],
            'start_time' => '2026-11-20 09:00:00', 'end_time' => '2026-11-20 10:00:00', 'max_slot' => 10, 'status' => ClassScheduleStatus::Active,
        ];
        auth('api')->forgetUser();
        $client = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])));

        $id = $client->postJson('/api/v1/admin/timetable', $body)->assertCreated()->json('data.id');
        Mail::assertQueued(GymNoticeMail::class, fn (GymNoticeMail $mail) => $mail->hasTo($first->email) && str_contains($mail->notice, '09:00 20/11/2026'));

        $client->patchJson("/api/v1/admin/timetable/{$id}", [...$body, 'max_slot' => 12])->assertOk();
        Mail::assertQueued(GymNoticeMail::class, 1);

        $client->patchJson("/api/v1/admin/timetable/{$id}", [...$body, 'trainer_id' => $second->id])->assertOk();
        Mail::assertQueued(GymNoticeMail::class, fn (GymNoticeMail $mail) => $mail->hasTo($second->email));
    }

    public function test_admin_manages_timetable(): void
    {
        $client = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])));
        $body = [
            'fitness_class_id' => $this->class->id,
            'trainer_id' => $this->trainer->id,
            'room' => 'P101',
            'start_time' => '2026-11-20T07:00',
            'end_time' => '2026-11-20T08:00',
            'max_slot' => 10,
            'status' => ClassScheduleStatus::Active,
        ];

        $client->getJson('/api/v1/admin/timetable/options')
            ->assertJsonPath('data.classes.0.name', 'Yoga Flow')
            ->assertJsonPath('data.trainers.0.name', 'Thu Hà')
            ->assertJsonPath('data.rooms.0', 'P101');

        $id = $client->postJson('/api/v1/admin/timetable', $body)
            ->assertCreated()
            ->assertJsonPath('data.start_time', '2026-11-20T07:00:00')
            ->json('data.id');

        $client->patchJson("/api/v1/admin/timetable/{$id}", [...$body, 'room' => 'P202', 'status' => ClassScheduleStatus::Cancelled])
            ->assertOk()
            ->assertJsonPath('data.room', 'P202')
            ->assertJsonPath('data.status', ClassScheduleStatus::Cancelled);

        $client->getJson('/api/v1/admin/timetable?q=Thu&status=cancelled&date=2026-11-20')->assertJsonCount(1, 'data');
        $client->getJson('/api/v1/admin/timetable?status=active')->assertJsonCount(0, 'data');
    }

    public function test_timetable_validation(): void
    {
        $member = User::factory()->create();

        $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])))
            ->postJson('/api/v1/admin/timetable', [
                'fitness_class_id' => 9999,
                'trainer_id' => $member->id,
                'room' => 'Z',
                'start_time' => '2026-11-20T08:00',
                'end_time' => '2026-11-20T07:00',
                'max_slot' => 0,
                'status' => 'x',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fitness_class_id', 'trainer_id', 'room', 'end_time', 'max_slot', 'status']);
    }

    public function test_new_schedule_must_start_in_future_and_be_active(): void
    {
        $client = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])));
        $body = [
            'fitness_class_id' => $this->class->id,
            'trainer_id' => $this->trainer->id,
            'room' => 'P101',
            'start_time' => '2026-11-10T09:00',
            'end_time' => '2026-11-10T10:30',
            'max_slot' => 10,
            'status' => ClassScheduleStatus::Active,
        ];

        $client->postJson('/api/v1/admin/timetable', $body)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);
        $client->postJson('/api/v1/admin/timetable', [...$body, 'start_time' => '2026-11-20T09:00', 'end_time' => '2026-11-20T10:00', 'status' => ClassScheduleStatus::Cancelled])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_past_schedule_can_be_edited_only_when_start_time_is_kept(): void
    {
        $client = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])));
        $body = [
            'fitness_class_id' => $this->class->id,
            'trainer_id' => $this->trainer->id,
            'room' => 'P101',
            'start_time' => '2026-11-10T12:00',
            'end_time' => '2026-11-10T13:00',
            'max_slot' => 10,
            'status' => ClassScheduleStatus::Active,
        ];
        $id = $client->postJson('/api/v1/admin/timetable', $body)->assertCreated()->json('data.id');

        Carbon::setTestNow('2026-11-10 14:00:00');

        $client->patchJson("/api/v1/admin/timetable/{$id}", [...$body, 'room' => 'P202'])->assertOk();
        $client->patchJson("/api/v1/admin/timetable/{$id}", [...$body, 'start_time' => '2026-11-10T11:00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_max_slot_cannot_go_below_booked_count(): void
    {
        $schedule = $this->createSchedule();
        $this->book($schedule, $this->createOwnedPackage())->assertCreated();
        $this->book($schedule, $this->createOwnedPackage())->assertUnprocessable();

        $other = User::factory()->create();
        auth('api')->forgetUser();
        $this->asMember($other)->postJson('/api/v1/bookings', ['class_schedule_id' => $schedule->id, 'user_package_id' => $this->createOwnedPackage($other)->id])->assertCreated();

        auth('api')->forgetUser();
        $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Admin])))
            ->patchJson("/api/v1/admin/timetable/{$schedule->id}", [
                'fitness_class_id' => $this->class->id,
                'trainer_id' => $this->trainer->id,
                'room' => 'P101',
                'start_time' => '2026-11-12T09:00',
                'end_time' => '2026-11-12T10:00',
                'max_slot' => 1,
                'status' => ClassScheduleStatus::Active,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_slot');
    }

    public function test_permissions(): void
    {
        $schedule = $this->createSchedule();
        $package = $this->createOwnedPackage();
        $bookingId = $this->book($schedule, $package)->json('data.id');

        auth('api')->forgetUser();
        $receptionist = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Receptionist])));
        $receptionist->getJson('/api/v1/admin/bookings/members')->assertOk();
        $receptionist->getJson('/api/v1/admin/timetable')->assertForbidden();
        $receptionist->patchJson("/api/v1/admin/bookings/{$bookingId}/cancel")->assertForbidden();

        auth('api')->forgetUser();
        $trainer = $this->withToken(auth('api')->login($this->trainer));
        $trainer->getJson('/api/v1/admin/bookings/members')->assertForbidden();
        $trainer->getJson('/api/v1/schedules')->assertForbidden();

        auth('api')->forgetUser();
        $this->asMember()->getJson('/api/v1/admin/timetable')->assertForbidden();
    }

    public function test_admin_booking_members_list_and_detail(): void
    {
        $package = $this->createOwnedPackage();
        $this->createOwnedPackage(overrides: ['package_type' => PackageType::PrivatePack, 'name' => 'Private']);
        $this->book($this->createSchedule(), $package)->assertCreated();
        Carbon::setTestNow('2026-11-12 11:00:00');

        auth('api')->forgetUser();
        $client = $this->withToken(auth('api')->login(User::factory()->create(['role' => Role::Receptionist])));

        $client->getJson('/api/v1/admin/bookings/members')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.remaining_credits', 9)
            ->assertJsonPath('data.0.completed_count', 1)
            ->assertJsonPath('data.0.status', 'active');

        $client->getJson("/api/v1/admin/bookings/members/{$package->id}")
            ->assertJsonPath('data.bookings.0.class_name', 'Yoga Flow')
            ->assertJsonPath('data.bookings.0.is_finished', true);
    }
}
