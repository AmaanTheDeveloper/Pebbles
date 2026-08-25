<?php

namespace Database\Factories;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'discount_type' => CouponDiscountType::Fixed,
            'discount_value' => '100.00',
            'minimum_order_amount' => null,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'usage_count' => 0,
            'per_customer_limit' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
            'branch_id' => null,
            'created_by' => User::factory()->superAdmin(),
        ];
    }

    public function percentage(float $value = 10, ?float $max = null): static
    {
        return $this->state(fn () => [
            'discount_type' => CouponDiscountType::Percentage,
            'discount_value' => number_format($value, 2, '.', ''),
            'maximum_discount_amount' => $max !== null ? number_format($max, 2, '.', '') : null,
        ]);
    }

    public function fixed(float $value = 100): static
    {
        return $this->state(fn () => [
            'discount_type' => CouponDiscountType::Fixed,
            'discount_value' => number_format($value, 2, '.', ''),
            'maximum_discount_amount' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
