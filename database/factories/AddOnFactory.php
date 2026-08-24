<?php

namespace Database\Factories;

use App\Models\AddOn;
use App\Models\AddOnGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddOn>
 */
class AddOnFactory extends Factory
{
    protected $model = AddOn::class;

    public function definition(): array
    {
        return [
            'add_on_group_id' => AddOnGroup::factory(),
            'name' => fake()->unique()->words(2, true),
            'price' => fake()->randomElement(['50.00', '80.00', '100.00']),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
