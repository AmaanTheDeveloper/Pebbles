<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Office', 'Other']),
            'recipient_name' => fake()->name(),
            'phone' => '03'.fake()->numerify('#########'),
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'area' => 'DHA Phase 7',
            'city' => 'Karachi',
            'instructions' => fake()->optional()->sentence(),
            'delivery_zone_id' => null,
            'is_default' => false,
        ];
    }
}
