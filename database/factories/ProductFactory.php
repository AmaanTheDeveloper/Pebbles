<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'base_price' => fake()->randomElement(['349.00', '499.00', '799.00', '1299.00']),
            'is_active' => true,
            'is_available' => true,
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }
}
