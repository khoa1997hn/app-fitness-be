<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageType;
use App\Share\Enums\PrivateDiscipline;
use App\Share\Enums\Role;
use App\Share\Enums\ScheduleCancelRequestStatus;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\FitnessClass;
use App\Share\Models\Order;
use App\Share\Models\Package;
use App\Share\Models\PrivateRequest;
use App\Share\Models\ScheduleCancelRequest;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use App\Share\Services\Gym\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTeachingFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $receptionist;

    private User $trainer;

    private User $otherTrainer;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 10:00:00');
        $this->admin = User::factory()->create(['role' => Role::Admin]);
        $this->receptionist = User::factory()->create(['role' => Role::Receptionist]);
        $this->trainer = User::factory()->create(['role' => Role::Trainer]);
        $this->otherTrainer = User::factory()->create(['role' => Role::Trainer]);
        $this->member = User::factory()->create();
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

    private function createUserPackage(string $type = PackageType::ClassPack, ?User $user = null): UserPackage
    {
        $user ??= $this->member;
        $package = Package::query()->create([
            'package_type' => $type,
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
            'package_type' => $type,
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

    private function createSchedule(string $start = '2026-11-10 18:00:00', ?User $trainer = null): ClassSchedule
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
            'max_slot' => 10,
            'status' => ClassScheduleStatus::Active,
        ]);
    }

    private function createBooking(ClassSchedule $schedule, ?UserPackage $package = null): Booking
    {
        $package ??= $this->createUserPackage();

        return Booking::query()->create([
            'user_id' => $package->user_id,
            'user_package_id' => $package->id,
            'class_schedule_id' => $schedule->id,
            'status' => BookingStatus::Confirmed,
        ]);
    }

    private function createPrivate(string $at = '2026-11-10 15:00:00', ?User $trainer = null, string $status = BookingRequestStatus::Confirmed): PrivateRequest
    {
        $package = $this->createUserPackage(PackageType::PrivatePack);

        return PrivateRequest::query()->create([
            'user_id' => $package->user_id,
            'user_package_id' => $package->id,
            'discipline' => PrivateDiscipline::Yoga,
            'requested_at' => $at,
            'people_count' => 1,
            'extra_people_count' => 0,
            'trainer_id' => ($trainer ?? $this->trainer)->id,
            'status' => $status,
            'reviewed_by' => $status === BookingRequestStatus::Pending ? null : $this->admin->id,
        ]);
    }

    public function test_attendance_lists_only_classes_with_members_and_trainer_sees_own(): void
    {
        $own = $this->createSchedule();
        $this->createBooking($own);
        $other = $this->createSchedule('2026-11-10 19:00:00', $this->otherTrainer);
        $this->createBooking($other);
        $this->createSchedule('2026-11-10 20:00:00');
        $this->createPrivate();

        $this->as($this->receptionist)->getJson('/api/v1/admin/attendance/sessions')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.editable', true);

        $this->as($this->trainer)->getJson('/api/v1/admin/attendance/sessions')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
    }

    public function test_mark_attendance_updates_record_and_does_not_deduct_credit(): void
    {
        $package = $this->createUserPackage();
        $booking = $this->createBooking($this->createSchedule(), $package);

        $this->as($this->receptionist)->postJson('/api/v1/admin/attendance/records', ['booking_id' => $booking->id, 'status' => 'absent'])
            ->assertOk()->assertJsonPath('data.members.0.attendance_status', 'absent');
        $this->as($this->receptionist)->postJson('/api/v1/admin/attendance/records', ['booking_id' => $booking->id, 'status' => 'present'])
            ->assertOk()->assertJsonPath('data.members.0.attendance_status', 'present');

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertSame(5, $package->refresh()->remaining_credits);
    }

    public function test_absent_member_still_loses_credit_after_class(): void
    {
        $package = $this->createUserPackage();
        $booking = $this->createBooking($this->createSchedule(), $package);
        $this->as($this->admin)->postJson('/api/v1/admin/attendance/records', ['booking_id' => $booking->id, 'status' => 'absent'])->assertOk();

        Carbon::setTestNow('2026-11-10 20:00:00');
        app(BookingService::class)->deductFinishedBookings();

        $this->assertSame(4, $package->refresh()->remaining_credits);
    }

    public function test_attendance_only_on_class_day(): void
    {
        $booking = $this->createBooking($this->createSchedule('2026-11-11 18:00:00'));

        $this->as($this->admin)->postJson('/api/v1/admin/attendance/records', ['booking_id' => $booking->id, 'status' => 'present'])
            ->assertUnprocessable();
        $this->as($this->admin)->postJson("/api/v1/admin/attendance/sessions/{$booking->class_schedule_id}/mark-all-present")
            ->assertUnprocessable();
    }

    public function test_cancelled_booking_cannot_be_marked(): void
    {
        $booking = $this->createBooking($this->createSchedule());
        $booking->update(['status' => BookingStatus::Cancelled]);

        $this->as($this->admin)->postJson('/api/v1/admin/attendance/records', ['booking_id' => $booking->id, 'status' => 'present'])
            ->assertUnprocessable();
    }

    public function test_trainer_can_only_mark_own_class(): void
    {
        $schedule = $this->createSchedule();
        $booking = $this->createBooking($schedule);

        $other = $this->as($this->otherTrainer);
        $other->postJson('/api/v1/admin/attendance/records', ['booking_id' => $booking->id, 'status' => 'present'])->assertNotFound();
        $other->postJson("/api/v1/admin/attendance/sessions/{$schedule->id}/mark-all-present")->assertNotFound();

        $this->as($this->trainer)->postJson("/api/v1/admin/attendance/sessions/{$schedule->id}/mark-all-present")
            ->assertOk()->assertJsonPath('data.members.0.attendance_status', 'present');
    }

    public function test_absent_warning_counts_last_five_records(): void
    {
        $package = $this->createUserPackage();

        foreach (range(1, 2) as $day) {
            $old = $this->createBooking($this->createSchedule("2026-11-0{$day} 18:00:00"), $package);
            $old->attendanceRecord()->create(['status' => 'absent', 'attendance_date' => "2026-11-0{$day}"]);
        }
        foreach (range(3, 7) as $day) {
            $recent = $this->createBooking($this->createSchedule("2026-11-0{$day} 18:00:00"), $package);
            $recent->attendanceRecord()->create(['status' => $day === 7 ? 'absent' : 'present', 'attendance_date' => "2026-11-0{$day}"]);
        }
        $cancelled = $this->createBooking($this->createSchedule('2026-11-09 18:00:00'), $package);
        $cancelled->attendanceRecord()->create(['status' => 'absent', 'attendance_date' => '2026-11-09']);
        $cancelled->update(['status' => BookingStatus::Cancelled]);
        $this->createBooking($this->createSchedule(), $package);

        $this->as($this->admin)->getJson('/api/v1/admin/attendance/sessions')
            ->assertOk()->assertJsonPath('data.0.members.0.absent_recent', 1);
    }

    public function test_teaching_list_shows_all_classes_and_reviewed_privates(): void
    {
        $booked = $this->createSchedule();
        $this->createBooking($booked);
        $unbooked = $this->createSchedule('2026-11-10 19:00:00');
        $private = $this->createPrivate();
        $this->createPrivate(status: BookingRequestStatus::Pending);
        $this->createPrivate(status: BookingRequestStatus::Cancelled)->update(['reviewed_by' => null]);

        $response = $this->as($this->admin)->getJson('/api/v1/admin/schedules')->assertOk()->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.id', $unbooked->id)->assertJsonPath('data.0.student_count', 0);
        $response->assertJsonPath('data.1.type', 'class')->assertJsonPath('data.1.id', $booked->id);
        $response->assertJsonPath('data.2.type', 'private')->assertJsonPath('data.2.id', $private->id);
        $response->assertJsonPath('data.2.end_time', '2026-11-10T16:00:00');
        $this->as($this->admin)->getJson("/api/v1/admin/schedules/class/{$unbooked->id}")->assertOk()->assertJsonCount(0, 'data.students');

        $this->as($this->admin)->getJson('/api/v1/admin/schedules?type=private')->assertJsonCount(1, 'data');
        $this->as($this->admin)->getJson('/api/v1/admin/schedules?status=completed')->assertJsonCount(0, 'data');
    }

    public function test_trainer_only_sees_own_teaching_and_receptionist_only_views(): void
    {
        $own = $this->createSchedule();
        $this->createBooking($own);
        $other = $this->createSchedule('2026-11-10 19:00:00', $this->otherTrainer);
        $this->createBooking($other);

        $trainer = $this->as($this->trainer);
        $trainer->getJson('/api/v1/admin/schedules')->assertOk()->assertJsonCount(1, 'data');
        $trainer->getJson("/api/v1/admin/schedules/class/{$own->id}")->assertOk()->assertJsonCount(1, 'data.students');
        $trainer->getJson("/api/v1/admin/schedules/class/{$other->id}")->assertNotFound();
        $trainer->patchJson("/api/v1/admin/schedules/class/{$own->id}/cancel")->assertForbidden();

        $receptionist = $this->as($this->receptionist);
        $receptionist->getJson('/api/v1/admin/schedules')->assertOk()->assertJsonCount(2, 'data');
        $receptionist->getJson("/api/v1/admin/schedules/class/{$other->id}")->assertOk();
        $receptionist->patchJson("/api/v1/admin/schedules/class/{$own->id}/cancel")->assertForbidden();
        $receptionist->postJson("/api/v1/instructor/schedules/class/{$own->id}/cancel-request", ['reason' => 'Bận'])->assertForbidden();
    }

    public function test_admin_cancels_teaching_without_deducting(): void
    {
        $package = $this->createUserPackage();
        $schedule = $this->createSchedule();
        $booking = $this->createBooking($schedule, $package);
        $private = $this->createPrivate();

        $this->as($this->admin)->patchJson("/api/v1/admin/schedules/class/{$schedule->id}/cancel")
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->as($this->admin)->patchJson("/api/v1/admin/schedules/private/{$private->id}/cancel")
            ->assertOk()->assertJsonPath('data.status', 'cancelled');

        $this->assertTrue($booking->refresh()->status->is(BookingStatus::Cancelled));
        $this->assertSame(5, $package->refresh()->remaining_credits);
        $this->assertSame(5, $private->userPackage->refresh()->remaining_credits);

        $this->as($this->admin)->patchJson("/api/v1/admin/schedules/class/{$schedule->id}/cancel")->assertUnprocessable();
    }

    public function test_finished_class_cannot_be_cancelled(): void
    {
        $schedule = $this->createSchedule('2026-11-10 08:00:00');
        $this->createBooking($schedule);

        $this->as($this->admin)->patchJson("/api/v1/admin/schedules/class/{$schedule->id}/cancel")->assertUnprocessable();
        $this->assertTrue($schedule->refresh()->status->is(ClassScheduleStatus::Active));
    }

    public function test_trainer_can_request_cancel_within_lock_window_and_approval_keeps_credit(): void
    {
        $package = $this->createUserPackage();
        $schedule = $this->createSchedule('2026-11-10 12:00:00');
        $booking = $this->createBooking($schedule, $package);

        $requestId = $this->as($this->trainer)
            ->postJson("/api/v1/instructor/schedules/class/{$schedule->id}/cancel-request", ['reason' => 'Bị ốm'])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.schedule.has_pending_cancel_request', true)
            ->json('data.id');

        $this->as($this->trainer)
            ->postJson("/api/v1/instructor/schedules/class/{$schedule->id}/cancel-request", ['reason' => 'Lần nữa'])
            ->assertUnprocessable();

        $this->as($this->admin)->patchJson("/api/v1/admin/schedule-cancel-requests/{$requestId}/approve", ['note' => 'OK'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.review_note', 'OK')
            ->assertJsonPath('data.schedule.status', 'cancelled');

        $this->assertTrue($booking->refresh()->status->is(BookingStatus::Cancelled));

        Carbon::setTestNow('2026-11-10 20:00:00');
        app(BookingService::class)->deductFinishedBookings();
        $this->assertSame(5, $package->refresh()->remaining_credits);
    }

    public function test_private_cancel_request_approved_keeps_credit_and_rejected_keeps_schedule(): void
    {
        $rejected = $this->createPrivate();
        $approved = $this->createPrivate();
        $trainer = $this->as($this->trainer);

        $rejectId = $trainer->postJson("/api/v1/instructor/schedules/private/{$rejected->id}/cancel-request", ['reason' => 'Bận'])->assertOk()->json('data.id');
        $approveId = $trainer->postJson("/api/v1/instructor/schedules/private/{$approved->id}/cancel-request", ['reason' => 'Ốm'])->assertOk()->json('data.id');

        $admin = $this->as($this->admin);
        $admin->patchJson("/api/v1/admin/schedule-cancel-requests/{$rejectId}/reject")->assertOk()->assertJsonPath('data.status', 'rejected');
        $admin->patchJson("/api/v1/admin/schedule-cancel-requests/{$approveId}/approve")->assertOk();
        $admin->patchJson("/api/v1/admin/schedule-cancel-requests/{$approveId}/approve")->assertUnprocessable();

        $this->assertTrue($rejected->refresh()->status->is(BookingRequestStatus::Confirmed));
        $this->assertTrue($approved->refresh()->status->is(BookingRequestStatus::Cancelled));
        $this->assertSame(5, $approved->userPackage->refresh()->remaining_credits);
    }

    public function test_trainer_cannot_request_for_others_or_after_start(): void
    {
        $other = $this->createSchedule('2026-11-10 18:00:00', $this->otherTrainer);
        $this->createBooking($other);
        $started = $this->createPrivate('2026-11-10 09:00:00');

        $trainer = $this->as($this->trainer);
        $trainer->postJson("/api/v1/instructor/schedules/class/{$other->id}/cancel-request", ['reason' => 'x'])->assertNotFound();
        $trainer->postJson("/api/v1/instructor/schedules/private/{$started->id}/cancel-request", ['reason' => 'x'])->assertUnprocessable();
        $trainer->postJson("/api/v1/instructor/schedules/private/{$started->id}/cancel-request", [])->assertUnprocessable();

        $this->as($this->admin)->postJson("/api/v1/instructor/schedules/private/{$started->id}/cancel-request", ['reason' => 'x'])->assertForbidden();
    }

    public function test_approve_closes_request_when_schedule_already_cancelled(): void
    {
        $private = $this->createPrivate();
        $requestId = $this->as($this->trainer)->postJson("/api/v1/instructor/schedules/private/{$private->id}/cancel-request", ['reason' => 'Ốm'])->json('data.id');
        $private->update(['status' => BookingRequestStatus::Cancelled]);

        $this->as($this->admin)->patchJson("/api/v1/admin/schedule-cancel-requests/{$requestId}/approve")
            ->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_approve_refused_when_class_reassigned_or_finished(): void
    {
        $reassigned = $this->createSchedule();
        $this->createBooking($reassigned);
        $finished = $this->createPrivate('2026-11-10 15:00:00');
        $trainer = $this->as($this->trainer);
        $reassignedId = $trainer->postJson("/api/v1/instructor/schedules/class/{$reassigned->id}/cancel-request", ['reason' => 'Ốm'])->json('data.id');
        $finishedId = $trainer->postJson("/api/v1/instructor/schedules/private/{$finished->id}/cancel-request", ['reason' => 'Ốm'])->json('data.id');

        $reassigned->update(['trainer_id' => $this->otherTrainer->id]);
        $finished->update(['status' => BookingRequestStatus::Completed]);

        $admin = $this->as($this->admin);
        $admin->patchJson("/api/v1/admin/schedule-cancel-requests/{$reassignedId}/approve")->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/schedule-cancel-requests/{$finishedId}/approve")->assertUnprocessable();
        $admin->patchJson("/api/v1/admin/schedule-cancel-requests/{$reassignedId}/reject")->assertOk();

        $this->assertTrue($reassigned->refresh()->status->is(ClassScheduleStatus::Active));
        $this->assertDatabaseHas('schedule_cancel_requests', ['id' => $finishedId, 'status' => ScheduleCancelRequestStatus::Pending]);
    }

    public function test_cancel_request_list_is_admin_only_and_filters_status(): void
    {
        $private = $this->createPrivate();
        ScheduleCancelRequest::query()->create([
            'private_request_id' => $private->id,
            'trainer_id' => $this->trainer->id,
            'reason' => 'Ốm',
            'status' => ScheduleCancelRequestStatus::Pending,
        ]);

        $admin = $this->as($this->admin);
        $admin->getJson('/api/v1/admin/schedule-cancel-requests?status=pending')->assertOk()->assertJsonCount(1, 'data');
        $admin->getJson('/api/v1/admin/schedule-cancel-requests?status=rejected')->assertOk()->assertJsonCount(0, 'data');

        $this->as($this->trainer)->getJson('/api/v1/admin/schedule-cancel-requests')->assertForbidden();
    }
}
