<?php

namespace Database\Seeders;

use App\Share\Models\Tour;
use Illuminate\Database\Seeder;

class ToursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tour::factory(100)->create();
    }
}
