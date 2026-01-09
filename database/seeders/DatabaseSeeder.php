<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Chỉ seed các dữ liệu bắt buộc khi hệ thống chạy
        $this->call(AdminsSeeder::class);
        $this->call(SettingsSeeder::class);
    }
}
