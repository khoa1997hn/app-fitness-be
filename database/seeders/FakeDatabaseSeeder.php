<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FakeDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeder này dùng để seed dữ liệu fake cho testing, không bắt buộc khi hệ thống chạy.
     */
    public function run(): void
    {
        $this->call(UsersSeeder::class);
        $this->call(BannersSeeder::class);
        $this->call(ProgramsSeeder::class);
        $this->call(SubscriptionsSeeder::class);
        $this->call(LessonFavoritesSeeder::class);
    }
}
