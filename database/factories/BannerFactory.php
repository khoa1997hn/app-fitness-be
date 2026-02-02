<?php

namespace Database\Factories;

use App\Share\Attributes\File;
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
        $locale = app()->getFallbackLocale();

        return [
            'description' => fake()->optional()->sentence(),
            $locale => [
                'image' => new File(
                    path: 'path/to/image.jpg',
                    name: 'image.jpg',
                    extension: 'jpg',
                    size: 1000,
                ),
                'link_url' => fake()->optional()->url(),
                'order' => fake()->numberBetween(0, 100),
                'is_active' => fake()->boolean(80),
            ],
        ];
    }
}
