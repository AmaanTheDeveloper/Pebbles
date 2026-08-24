<?php

namespace Database\Factories;

use App\Models\SiteContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteContent>
 */
class SiteContentFactory extends Factory
{
    protected $model = SiteContent::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'content' => [
                'headline' => fake()->sentence(),
            ],
        ];
    }
}
