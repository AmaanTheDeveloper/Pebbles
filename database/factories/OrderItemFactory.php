<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice = '499.00';
        $quantity = 2;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'product_name' => 'Chocolate Brownie',
            'variant_name' => 'Regular',
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => '998.00',
        ];
    }
}
