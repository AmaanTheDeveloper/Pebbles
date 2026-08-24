<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    protected $model = Banner::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(6),
            'image_path' => 'banners/'.fake()->uuid().'.jpg',
            'link_url' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
