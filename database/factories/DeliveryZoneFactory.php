<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    protected $model = DeliveryZone::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->citySuffix().' Zone',
            'delivery_fee' => fake()->randomElement(['99.00', '149.00', '199.00']),
            'minimum_order_amount' => '500.00',
            'estimated_minutes' => fake()->numberBetween(30, 60),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
