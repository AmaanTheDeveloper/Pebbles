<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemAddOn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemAddOn>
 */
class OrderItemAddOnFactory extends Factory
{
    protected $model = OrderItemAddOn::class;

    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'add_on_id' => null,
            'add_on_name' => 'Extra Chocolate',
            'unit_price' => '80.00',
            'quantity' => 1,
            'line_total' => '80.00',
        ];
    }
}
