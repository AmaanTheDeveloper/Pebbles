<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->unique()->randomElement(['Regular', 'Large', 'Family', '500ml', '1 Liter', 'Mini']),
            'sku' => Str::upper(fake()->bothify('PB-###??')),
            'price' => fake()->randomElement(['399.00', '599.00', '899.00']),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
