<?php

namespace Database\Factories;

use App\Enums\OfferType;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement(OfferType::cases()),
            'short_description' => fake()->sentence(8),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'offer_value' => fake()->optional()->randomElement(['10% off', 'Rs 200 off', 'Buy 1 Get 1']),
            'cta_label' => 'Order Now',
            'cta_url' => '/order',
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(10),
        ]);
    }
}
