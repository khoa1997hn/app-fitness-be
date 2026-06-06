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
     * Object key S3 đã upload sẵn — seeder random từ pool.
     *
     * @var list<string>
     */
    private const BANNER_IMAGE_PATHS = [
        'banner/image/dSflmHXlFnqBJO84yduaJ3Y2S4l0ccNlp0zfnYG8.webp',
        'banner/image/Raffh0eAMM85t2etJNTNRS0sPXGnDb7oBaAmtoN0.webp',
        'banner/image/EHt7KM5lB88MFlFNncOjyCqmPr9WwfGub2YOJlj5.jpg',
    ];

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
        $localeData = [
            'image' => $this->randomImageFile(),
            'link_url' => fake()->optional()->url(),
            'order' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(80),
        ];

        return [
            'description' => fake()->optional()->sentence(),
            'vi' => $localeData,
            'en' => $localeData,
        ];
    }

    private function randomImageFile(): File
    {
        return $this->fileFromPath($this->pickRandomPath(self::BANNER_IMAGE_PATHS));
    }

    /**
     * @param  list<string>  $paths
     */
    private function pickRandomPath(array $paths): string
    {
        return $paths[array_rand($paths)];
    }

    private function fileFromPath(string $path, ?int $size = null): File
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: null;

        return new File(
            path: $path,
            name: basename($path),
            extension: $extension,
            size: $size ?? 512_000,
        );
    }
}
