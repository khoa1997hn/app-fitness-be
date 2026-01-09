<?php

namespace Database\Factories;

use App\Share\Enums\FormType;
use App\Share\Models\TrackingEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Share\Models\TrackingEmail>
 */
class TrackingEmailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = TrackingEmail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_type' => FormType::EVisa,
            'email' => fake()->unique()->safeEmail(),
            'data' => [
                'name' => fake()->name(),
                'phone' => fake()->optional()->phoneNumber(),
                'country' => fake()->country(),
                'arrival_date' => fake()->date(),
                'departure_date' => fake()->date(),
                'visa_type' => fake()->randomElement(['single', 'multiple']),
                'processing_time' => fake()->randomElement(['normal', 'urgent', 'express']),
                'additional_info' => fake()->optional()->sentence(),
            ],
        ];
    }
}
