<?php

namespace Database\Factories;

use App\Models\HomepageCtaCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageCtaCard>
 */
class HomepageCtaCardFactory extends Factory
{
    protected $model = HomepageCtaCard::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'link_url' => '/menu',
            'tone' => fake()->randomElement(['orange', 'green', 'white']),
            'display_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
