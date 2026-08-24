<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'rating' => fake()->numberBetween(4, 5),
            'review' => fake()->paragraph(),
            'status' => ReviewStatus::Pending,
            'is_featured' => false,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Rejected,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Approved,
            'is_featured' => true,
        ]);
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(function () use ($user): array {
            $account = $user ?? User::factory()->create();

            return [
                'user_id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
            ];
        });
    }
}
