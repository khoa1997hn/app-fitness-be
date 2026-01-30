<?php

namespace Database\Seeders;

use App\Share\Models\Banner;
use Illuminate\Database\Seeder;

class BannersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Banner::factory(10)->create();
    }
}
