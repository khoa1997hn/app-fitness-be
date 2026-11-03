<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Booking đang giữ buổi theo quy tắc cũ được trừ luôn, vì từ giờ buổi bị trừ lúc đặt.
     */
    public function up(): void
    {
        DB::table('bookings')
            ->join('class_schedules', 'class_schedules.id', '=', 'bookings.class_schedule_id')
            ->where('bookings.status', 'confirmed')
            ->where('class_schedules.status', 'active')
            ->whereNull('bookings.credit_deducted_at')
            ->select('bookings.id', 'bookings.user_package_id')
            ->orderBy('bookings.id')
            ->get()
            ->each(function (object $booking) {
                DB::transaction(function () use ($booking) {
                    DB::table('user_packages')
                        ->where('id', $booking->user_package_id)
                        ->where('remaining_credits', '>', 0)
                        ->decrement('remaining_credits');

                    DB::table('bookings')->where('id', $booking->id)->update(['credit_deducted_at' => now()]);
                });
            });
    }

    public function down(): void {}
};
