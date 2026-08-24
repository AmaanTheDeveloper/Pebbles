<?php

namespace Database\Factories;

use App\Models\AddOn;
use App\Models\CartItem;
use App\Models\CartItemAddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItemAddOn>
 */
class CartItemAddOnFactory extends Factory
{
    protected $model = CartItemAddOn::class;

    public function definition(): array
    {
        return [
            'cart_item_id' => CartItem::factory(),
            'add_on_id' => AddOn::factory(),
            'quantity' => 1,
        ];
    }
}
