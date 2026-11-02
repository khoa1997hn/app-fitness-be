<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\AttendanceStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\AttendanceRecord;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Điểm danh lớp gói thường. Không trừ buổi: `gym:deduct-credits` trừ mọi booking đã qua giờ.
 */
class AttendanceService
{
    public function mark(Booking $booking, string $status, User $staff): AttendanceRecord
    {
        if (! $booking->status->is(BookingStatus::Confirmed)) {
            throw new GymException(__('messages.gym_attendance_invalid_booking'));
        }

        $this->assertMarkable($booking->classSchedule);

        return AttendanceRecord::query()->updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'status' => $status,
                'attendance_date' => $booking->classSchedule->start_time->toDateString(),
                'marked_by' => $staff->id,
            ],
        );
    }

    public function markAllPresent(ClassSchedule $schedule, User $staff): void
    {
        $this->assertMarkable($schedule);

        DB::transaction(function () use ($schedule, $staff) {
            $schedule->confirmedBookings()->with('classSchedule')->get()
                ->each(fn (Booking $booking) => $this->mark($booking, AttendanceStatus::Present, $staff));
        });
    }

    public function isMarkable(ClassSchedule $schedule): bool
    {
        return $schedule->status->is(ClassScheduleStatus::Active) && $schedule->start_time->isToday();
    }

    /**
     * Số buổi vắng trong 5 lần điểm danh gần nhất của từng học viên.
     *
     * @param  Collection<int, int>  $userIds
     * @return array<int, int>
     */
    public function recentAbsences(Collection $userIds): array
    {
        return AttendanceRecord::query()
            ->join('bookings', 'bookings.id', '=', 'attendance_records.booking_id')
            ->whereIn('bookings.user_id', $userIds)
            ->where('bookings.status', BookingStatus::Confirmed)
            ->orderByDesc('attendance_records.attendance_date')
            ->get(['bookings.user_id', 'attendance_records.status'])
            ->groupBy('user_id')
            ->map(fn (Collection $records) => $records->take(5)->filter(fn (AttendanceRecord $record) => $record->status->is(AttendanceStatus::Absent))->count())
            ->all();
    }

    private function assertMarkable(ClassSchedule $schedule): void
    {
        if (! $this->isMarkable($schedule)) {
            throw new GymException(__('messages.gym_attendance_today_only'));
        }
    }
}
