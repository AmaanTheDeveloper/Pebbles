<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $name = 'Pebbles - '.fake()->unique()->streetName();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => Str::upper(Str::random(6)),
            'address' => fake()->streetAddress().', Karachi',
            'phone' => '03'.fake()->numerify('#########'),
            'latitude' => fake()->latitude(24.7, 25.0),
            'longitude' => fake()->longitude(66.9, 67.2),
            'opens_at' => '11:00:00',
            'closes_at' => '23:00:00',
            'is_active' => true,
            'preparation_minutes' => 20,
            'delivery_minutes' => 30,
        ];
    }
}
