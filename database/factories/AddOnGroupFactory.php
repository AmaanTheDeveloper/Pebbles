<?php

namespace Database\Factories;

use App\Models\AddOnGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AddOnGroup>
 */
class AddOnGroupFactory extends Factory
{
    protected $model = AddOnGroup::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Extras', 'Sauces', 'Toppings']).' '.fake()->unique()->numerify('##');

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_required' => false,
            'min_selections' => 0,
            'max_selections' => 3,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
