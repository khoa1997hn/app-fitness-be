<?php

namespace Database\Factories;

use App\Share\Enums\DiscountType;
use App\Share\Models\Admin;
use App\Share\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Share\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminIds = Admin::query()->pluck('id')->toArray();
        $discountType = fake()->randomElement([DiscountType::Percentage, DiscountType::FixedAmount]);
        $totalQuantity = fake()->numberBetween(10, 1000);
        $usedQuantity = fake()->numberBetween(0, min($totalQuantity, 500));

        // Nếu là phần trăm thì giá trị từ 1-100, nếu là số tiền thì từ 1-1000
        $discountValue = $discountType === DiscountType::Percentage
            ? fake()->randomFloat(2, 1, 100)
            : fake()->randomFloat(2, 1, 1000);

        $startAt = fake()->optional(0.8)->dateTimeBetween('-1 month', '+1 month');
        $endAt = $startAt ? fake()->dateTimeBetween($startAt, '+3 months') : null;

        return [
            'code' => strtoupper(fake()->unique()->bothify('COUPON-####-????')),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'total_quantity' => $totalQuantity,
            'used_quantity' => $usedQuantity,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'creator_id' => ! empty($adminIds) ? fake()->randomElement($adminIds) : null,
            'updater_id' => fake()->optional(0.3)->randomElement($adminIds),
        ];
    }
}
