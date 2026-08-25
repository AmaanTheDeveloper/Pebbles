<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guest_token' => null,
            'branch_id' => null,
        ];
    }

    public function guest(?string $token = null): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'guest_token' => $token ?? (string) Str::uuid(),
        ]);
    }
}
