<?php

namespace Database\Factories;

use App\Share\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Share\Models\Banner>
 */
class BannerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Banner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_url' => fake()->imageUrl(800, 400, 'business', true),
            'link_url' => fake()->optional()->url(),
            'description' => fake()->optional()->sentence(),
            'order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
