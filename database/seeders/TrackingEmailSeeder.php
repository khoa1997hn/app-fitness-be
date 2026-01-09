<?php

namespace Database\Seeders;

use App\Share\Models\TrackingEmail;
use Illuminate\Database\Seeder;

class TrackingEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TrackingEmail::factory(100)->create();
    }
}
