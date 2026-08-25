<?php

namespace App\Actions;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\User;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

class ResolveCouponDiscount
{
    /**
     * Resolve a manually applied coupon against the current cart/order context.
     *
     * Coupon applies after automatic offer discount, against the remaining
     * merchandise subtotal. Delivery fees are never discounted by coupons.
     *
     * @return array{
     *     discount_amount: string,
     *     coupon_id: int,
     *     coupon_code: string,
     *     coupon: Coupon
     * }
     */
    public function handle(
        Coupon $coupon,
        ?User $user,
        float|int|string $subtotal,
        float|int|string $offerDiscountAmount = 0,
        ?int $branchId = null,
        bool $forCheckout = false,
    ): array {
        $subtotalAmount = max(0.0, (float) $subtotal);
        $offerAmount = max(0.0, (float) $offerDiscountAmount);
        $eligibleAmount = max(0.0, $subtotalAmount - $offerAmount);

        $this->assertEligible($coupon, $user, $subtotalAmount, $branchId, $forCheckout);

        $amount = $this->calculateAmount($coupon, $eligibleAmount);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon cannot be applied to the current cart.',
            ]);
        }

        return [
            'discount_amount' => Money::of($amount),
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'coupon' => $coupon,
        ];
    }

    public function findByCode(string $code): Coupon
    {
        $normalized = Coupon::normalizeCode($code);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'coupon_code' => 'Invalid coupon code.',
            ]);
        }

        $coupon = Coupon::query()->where('code', $normalized)->first();

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Invalid coupon code.',
            ]);
        }

        return $coupon;
    }

    /**
     * Soft validation used when re-checking an already-attached cart coupon.
     * Returns null when the coupon should be cleared silently/with message.
     *
     * @return array{discount_amount: string, coupon_id: int, coupon_code: string, coupon: Coupon}|null
     */
    public function tryResolve(
        ?Coupon $coupon,
        ?User $user,
        float|int|string $subtotal,
        float|int|string $offerDiscountAmount = 0,
        ?int $branchId = null,
    ): ?array {
        if ($coupon === null) {
            return null;
        }

        try {
            return $this->handle($coupon, $user, $subtotal, $offerDiscountAmount, $branchId, forCheckout: false);
        } catch (ValidationException) {
            return null;
        }
    }

    public function assertEligible(
        Coupon $coupon,
        ?User $user,
        float $subtotalAmount,
        ?int $branchId,
        bool $forCheckout,
    ): void {
        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Invalid coupon code.',
            ]);
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon is not active yet.',
            ]);
        }

        if ($coupon->expires_at !== null && $coupon->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon has expired.',
            ]);
        }

        if ($coupon->branch_id !== null) {
            if ($branchId === null || (int) $coupon->branch_id !== (int) $branchId) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'This coupon is not available for the selected branch.',
                ]);
            }
        }

        if ($coupon->minimum_order_amount !== null && $subtotalAmount < (float) $coupon->minimum_order_amount) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon requires a minimum order of Rs. '.number_format((float) $coupon->minimum_order_amount, 0, '.', ',').'.',
            ]);
        }

        if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon has reached its usage limit.',
            ]);
        }

        if ($user !== null && $coupon->per_customer_limit !== null) {
            $usedByCustomer = CouponRedemption::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();

            if ($usedByCustomer >= $coupon->per_customer_limit) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'You have already used this coupon.',
                ]);
            }
        }

        if ($forCheckout && $coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'coupon_code' => 'This coupon has reached its usage limit.',
            ]);
        }
    }

    public function calculateAmount(Coupon $coupon, float $eligibleAmount): float
    {
        if ($eligibleAmount <= 0) {
            return 0.0;
        }

        $amount = match ($coupon->discount_type) {
            CouponDiscountType::Percentage => round($eligibleAmount * ((float) $coupon->discount_value / 100), 2),
            CouponDiscountType::Fixed => round((float) $coupon->discount_value, 2),
            default => 0.0,
        };

        if (
            $coupon->discount_type === CouponDiscountType::Percentage
            && $coupon->maximum_discount_amount !== null
        ) {
            $amount = min($amount, (float) $coupon->maximum_discount_amount);
        }

        return min(max(0.0, $amount), $eligibleAmount);
    }
}
