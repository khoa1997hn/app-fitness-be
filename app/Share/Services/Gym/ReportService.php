<?php

namespace App\Share\Services\Gym;

use App\Share\Enums\AttendanceStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Models\Booking;
use App\Share\Models\ClassSchedule;
use App\Share\Models\Event;
use App\Share\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Số liệu báo cáo. Doanh thu chỉ tính đơn đã thanh toán theo `paid_at`, giá theo snapshot trên đơn.
 * Vé đã thu mà sau đó bị hủy vẫn nằm trong doanh thu vì hoàn tiền còn làm thủ công.
 */
class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(Carbon $from, Carbon $to): array
    {
        $byType = $this->paidOrders($from, $to)
            ->selectRaw('type, SUM(amount) as revenue')
            ->groupBy('type')
            ->pluck('revenue', 'type');

        return [
            'total_revenue' => (int) $byType->sum(),
            'revenue_by_type' => collect(OrderType::getValues())
                ->mapWithKeys(fn (string $type) => [$type => (int) ($byType[$type] ?? 0)])
                ->all(),
            'new_members' => $this->firstPurchases($from, $to)->count(),
            ...$this->bookingStats($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function revenue(Carbon $from, Carbon $to): array
    {
        $byDate = $this->paidOrders($from, $to)
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue')
            ->groupBy('date')
            ->pluck('revenue', 'date');

        return [
            'series' => $this->fillDates($from, $to, $byDate),
            'breakdown' => $this->breakdown($from, $to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function members(Carbon $from, Carbon $to): array
    {
        $firstPurchases = $this->firstPurchases($from, $to);
        $buyers = $this->paidOrders($from, $to)
            ->whereIn('type', $this->packageOrderTypes())
            ->distinct()
            ->pluck('user_id');
        $returning = $buyers->count() - $firstPurchases->count();

        return [
            'series' => $this->fillDates($from, $to, $firstPurchases->countBy(fn (string $paidAt) => substr($paidAt, 0, 10))),
            'new_members' => $firstPurchases->count(),
            'buyers' => $buyers->count(),
            'renewal_rate' => $this->percent($returning, $buyers->count()),
            ...$this->bookingStats($from, $to),
        ];
    }

    /**
     * Chỉ tính buổi đã diễn ra. Tỷ lệ lấp đầy là trung bình của từng buổi.
     *
     * @return array<string, mixed>
     */
    public function classes(Carbon $from, Carbon $to): array
    {
        $schedules = ClassSchedule::query()
            ->with(['fitnessClass', 'trainer'])
            ->withCount([
                'confirmedBookings',
                'confirmedBookings as attended_count' => fn (Builder $query) => $query
                    ->whereHas('attendanceRecord', fn (Builder $attendance) => $attendance->where('status', AttendanceStatus::Present)),
            ])
            ->where('status', ClassScheduleStatus::Active)
            ->whereBetween('start_time', [$from, $to])
            ->where('end_time', '<=', now())
            ->get();

        return [
            'classes' => $this->fillStats($schedules, fn (ClassSchedule $schedule) => $schedule->fitnessClass->name),
            'subjects' => $this->fillStats($schedules, fn (ClassSchedule $schedule) => FitnessClassCategory::getDescription($schedule->fitnessClass->category->value)),
            'time_slots' => $this->fillStats($schedules, fn (ClassSchedule $schedule) => $schedule->start_time->format('H:i').'–'.$schedule->end_time->format('H:i'), false),
            'dates' => $this->fillStats($schedules, fn (ClassSchedule $schedule) => $schedule->start_time->toDateString(), false),
            'weekdays' => $this->fillStats($schedules, fn (ClassSchedule $schedule) => (string) $schedule->start_time->dayOfWeekIso, false),
            'trainers' => $this->fillStats($schedules, fn (ClassSchedule $schedule) => trim($schedule->trainer->first_name.' '.$schedule->trainer->last_name)),
        ];
    }

    /**
     * @return list<array{label: string, value: int|float, note: string, format: string}>
     */
    public function dashboard(Carbon $from, Carbon $to): array
    {
        $overview = $this->overview($from, $to);
        $period = $from->toDateString().' — '.$to->toDateString();

        return [
            $this->card(__('messages.gym_dashboard_revenue'), $overview['total_revenue'], $period, 'currency'),
            $this->card(__('messages.gym_dashboard_new_members'), $overview['new_members'], $period),
            $this->card(__('messages.gym_dashboard_bookings'), $overview['bookings'], $period),
            $this->card(__('messages.gym_dashboard_cancellation_rate'), $overview['cancellation_rate'], $period, 'percent'),
            $this->card(__('messages.gym_dashboard_staff_cancellations'), $overview['staff_cancellations'], $period.' · '.$overview['staff_cancellation_rate'].'%'),
            $this->card(__('messages.gym_dashboard_member_cancellations'), $overview['member_cancellations'], $period.' · '.$overview['member_cancellation_rate'].'%'),
            $this->card(__('messages.gym_dashboard_sessions'), ClassSchedule::query()->where('status', ClassScheduleStatus::Active)->whereBetween('start_time', [$from, $to])->count(), $period),
            $this->card(__('messages.gym_dashboard_paid_orders'), $this->paidOrders($from, $to)->count(), $period),
        ];
    }

    /**
     * @return array{label: string, value: int|float, note: string, format: string}
     */
    private function card(string $label, int|float $value, string $note, string $format = 'number'): array
    {
        return ['label' => $label, 'value' => $value, 'note' => $note, 'format' => $format];
    }

    private function paidOrders(Carbon $from, Carbon $to): Builder
    {
        return Order::query()
            ->where('status', OrderStatus::Success)
            ->whereBetween('paid_at', [$from, $to]);
    }

    /**
     * @return list<string>
     */
    private function packageOrderTypes(): array
    {
        return [OrderType::Package, OrderType::Therapy];
    }

    /**
     * Học viên mới là người mua gói đầu tiên trong kỳ, vì `users` có cả người dùng app chưa từng đến phòng tập.
     *
     * @return Collection<int, string> Thời điểm mua đầu tiên theo user
     */
    private function firstPurchases(Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->where('status', OrderStatus::Success)
            ->whereIn('type', $this->packageOrderTypes())
            ->selectRaw('user_id, MIN(paid_at) as first_paid_at')
            ->groupBy('user_id')
            ->havingBetween('first_paid_at', [$from, $to])
            ->pluck('first_paid_at', 'user_id');
    }

    /**
     * Lượt đặt và tỷ lệ hủy theo ngày tạo lịch. Tính mọi lượt hủy, gồm cả phòng tập hủy.
     *
     * @return array{bookings: int, cancellation_rate: float, member_cancellations: int, member_cancellation_rate: float, staff_cancellations: int, staff_cancellation_rate: float}
     */
    private function bookingStats(Carbon $from, Carbon $to): array
    {
        $bookings = Booking::query()->whereBetween('created_at', [$from, $to]);
        $total = (clone $bookings)->count();
        $cancelled = (clone $bookings)->where('status', BookingStatus::Cancelled)->count();

        // So sánh với chủ booking để không bị đổi nhóm khi người hủy đổi role sau này.
        $memberCancelled = (clone $bookings)->where('status', BookingStatus::Cancelled)
            ->whereColumn('cancelled_by', 'user_id')->count();
        $staffCancelled = (clone $bookings)->where('status', BookingStatus::Cancelled)
            ->whereColumn('cancelled_by', '!=', 'user_id')->count();

        return [
            'bookings' => $total,
            'cancellation_rate' => $this->percent($cancelled, $total),
            'member_cancellations' => $memberCancelled,
            'member_cancellation_rate' => $this->percent($memberCancelled, $total),
            'staff_cancellations' => $staffCancelled,
            'staff_cancellation_rate' => $this->percent($staffCancelled, $total),
        ];
    }

    /**
     * @return list<array{type: string, name: string, quantity_sold: int, revenue: int}>
     */
    private function breakdown(Carbon $from, Carbon $to): array
    {
        $rows = $this->paidOrders($from, $to)
            ->selectRaw('type, package_id, therapy_service_id, therapy_variant, event_id, MIN(name) as name, SUM(COALESCE(quantity, 1)) as quantity_sold, SUM(amount) as revenue')
            ->groupBy('type', 'package_id', 'therapy_service_id', 'therapy_variant', 'event_id')
            ->get();

        $events = Event::query()->whereIn('id', $rows->pluck('event_id')->filter())->get()->keyBy('id');

        return $rows
            ->map(fn (Order $row) => [
                'type' => $row->type->value,
                'name' => match ($row->type->value) {
                    OrderType::Event => $events[$row->event_id]->name,
                    OrderType::ExtraPeople, OrderType::Redlight => OrderType::getDescription($row->type->value),
                    default => $row->name,
                },
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (int) $row->revenue,
            ])
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int|string, int|string>  $byDate
     * @return list<array{date: string, value: int}>
     */
    private function fillDates(Carbon $from, Carbon $to, Collection $byDate): array
    {
        $series = [];

        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $date = $day->toDateString();
            $series[] = ['date' => $date, 'value' => (int) ($byDate[$date] ?? 0)];
        }

        return $series;
    }

    /**
     * @param  Collection<int, ClassSchedule>  $schedules
     * @return list<array{name: string, sessions: int, bookings: int, attended: int, fill_rate_pct: float}>
     */
    private function fillStats(Collection $schedules, callable $groupBy, bool $rankByBookings = true): array
    {
        $stats = $schedules
            ->groupBy($groupBy)
            ->map(fn (Collection $group, string $name) => [
                'name' => $name,
                'sessions' => $group->count(),
                'bookings' => (int) $group->sum('confirmed_bookings_count'),
                'attended' => (int) $group->sum('attended_count'),
                'fill_rate_pct' => round($group->avg(fn (ClassSchedule $schedule) => min(100, $schedule->confirmed_bookings_count / $schedule->max_slot * 100)), 1),
            ]);

        return ($rankByBookings ? $stats->sortByDesc('bookings') : $stats->sortKeys())
            ->values()
            ->all();
    }

    private function percent(int $part, int $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }
}
