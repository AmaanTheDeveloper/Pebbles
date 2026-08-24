<?php

namespace Database\Factories;

use App\Models\HomepageShowcaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageShowcaseItem>
 */
class HomepageShowcaseItemFactory extends Factory
{
    protected $model = HomepageShowcaseItem::class;

    public function definition(): array
    {
        return [
            'pillar_key' => fake()->randomElement(['ice-cream', 'shakes', 'faluda', 'special-picks']),
            'category_id' => null,
            'label' => fake()->words(2, true),
            'blurb' => fake()->sentence(),
            'badge' => null,
            'accent' => fake()->randomElement(['orange', 'green']),
            'is_special' => false,
            'display_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
