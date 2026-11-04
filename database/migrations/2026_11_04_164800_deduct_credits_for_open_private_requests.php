<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Từ nay buổi Private bị trừ ngay khi gửi đơn. Đơn đang mở tạo trước đây chưa bị trừ,
     * nên trừ bù để lúc hoàn thành không miễn phí và lúc hủy không hoàn thừa.
     */
    public function up(): void
    {
        $this->openRequestCounts()->each(function (int $count, int $packageId) {
            DB::table('user_packages')
                ->where('id', $packageId)
                ->whereNotNull('remaining_credits')
                ->update(['remaining_credits' => DB::raw('CASE WHEN remaining_credits > '.$count.' THEN remaining_credits - '.$count.' ELSE 0 END')]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->openRequestCounts()->each(function (int $count, int $packageId) {
            DB::table('user_packages')
                ->where('id', $packageId)
                ->whereNotNull('remaining_credits')
                ->increment('remaining_credits', $count);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function openRequestCounts(): \Illuminate\Support\Collection
    {
        return DB::table('private_requests')
            ->whereIn('status', ['pending', 'confirmed'])
            ->groupBy('user_package_id')
            ->pluck(DB::raw('COUNT(*)'), 'user_package_id')
            ->map(fn ($count) => (int) $count);
    }
};
