<?php

namespace Database\Seeders;

use App\Share\Models\Admin;
use Illuminate\Database\Seeder;

class AdminsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'password' => 'password',
            ]
        );
    }
}
