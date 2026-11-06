<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nâng giới hạn số người của gói Private Group từ 15 lên tối đa 100
 * (cả gói mẫu lẫn gói học viên đã mua, vì user_packages lưu snapshot).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setLimit(15, 100, '6–15 người', '6–100 người');
    }

    public function down(): void
    {
        $this->setLimit(100, 15, '6–100 người', '6–15 người');
    }

    private function setLimit(int $from, int $to, string $fromText, string $toText): void
    {
        DB::table('packages')
            ->where('package_type', 'private_group')
            ->where('base_people_max', $from)
            ->update(['base_people_max' => $to]);

        DB::table('user_packages')
            ->where('package_type', 'private_group')
            ->where('base_people_max', $from)
            ->update(['base_people_max' => $to]);

        DB::table('package_translations')
            ->whereIn('package_id', DB::table('packages')->where('package_type', 'private_group')->select('id'))
            ->update(['description' => DB::raw('REPLACE(description, '.DB::getPdo()->quote($fromText).', '.DB::getPdo()->quote($toText).')')]);
    }
};
