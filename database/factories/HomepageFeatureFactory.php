<?php

namespace Database\Factories;

use App\Models\HomepageFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageFeature>
 */
class HomepageFeatureFactory extends Factory
{
    protected $model = HomepageFeature::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'icon_letter' => strtoupper(fake()->randomLetter()),
            'accent' => 'from-brand-green/15 to-brand-green/5',
            'glow' => 'lift-card-green',
            'display_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
