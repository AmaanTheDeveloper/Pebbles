<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = '799.00';
        $deliveryFee = '149.00';

        return [
            'user_id' => User::factory(),
            'branch_id' => Branch::factory(),
            'type' => OrderType::Delivery,
            'status' => OrderStatus::Pending,
            'subtotal' => $subtotal,
            'discount_amount' => '0.00',
            'delivery_fee' => $deliveryFee,
            'tax_amount' => '0.00',
            'grand_total' => '948.00',
            'customer_notes' => null,
            'delivery_zone_name' => 'DHA Phase 7',
            'delivery_recipient_name' => fake()->name(),
            'delivery_phone' => '03'.fake()->numerify('#########'),
            'delivery_line1' => fake()->streetAddress(),
            'delivery_area' => 'DHA Phase 7',
            'delivery_city' => 'Karachi',
        ];
    }

    public function takeaway(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrderType::Takeaway,
            'delivery_fee' => '0.00',
            'grand_total' => $attributes['subtotal'] ?? '799.00',
            'delivery_zone_name' => null,
            'delivery_recipient_name' => null,
            'delivery_phone' => null,
            'delivery_line1' => null,
            'delivery_area' => null,
            'delivery_city' => null,
            'pickup_notes' => 'Ready in 20 minutes',
        ]);
    }

    public function dineIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => OrderType::DineIn,
            'delivery_fee' => '0.00',
            'grand_total' => $attributes['subtotal'] ?? '799.00',
            'table_number' => (string) fake()->numberBetween(1, 20),
            'delivery_zone_name' => null,
            'delivery_recipient_name' => null,
            'delivery_phone' => null,
            'delivery_line1' => null,
            'delivery_area' => null,
            'delivery_city' => null,
        ]);
    }
}
