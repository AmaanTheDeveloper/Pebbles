<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;

class RemoveCartCoupon
{
    public function __construct(
        private EnsureCustomerCart $ensureCustomerCart,
    ) {}

    public function handle(?User $user = null, ?string $guestToken = null): Cart
    {
        $cart = $this->ensureCustomerCart->handle($user, null, $guestToken);

        if ($cart->coupon_id !== null) {
            $cart->update(['coupon_id' => null]);
        }

        return $cart->fresh(['items.addOns', 'coupon', 'items.product', 'items.variant', 'items.addOns.addOn']);
    }
}
