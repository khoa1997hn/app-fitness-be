<?php

namespace Database\Seeders;

use App\Share\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::factory(100)->create();
    }
}
