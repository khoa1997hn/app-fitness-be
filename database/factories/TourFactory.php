<?php

namespace Database\Factories;

use App\Share\Models\Admin;
use App\Share\Models\Tour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Share\Models\Tour>
 */
class TourFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Tour::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminIds = Admin::query()->pluck('id')->toArray();

        return [
            'name' => fake()->words(3, true),
            'link' => fake()->url(),
            'creator_id' => ! empty($adminIds) ? fake()->randomElement($adminIds) : null,
            'updater_id' => fake()->optional(0.3)->randomElement($adminIds),
        ];
    }
}
