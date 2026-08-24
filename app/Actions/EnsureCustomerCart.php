<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;

class EnsureCustomerCart
{
    public function handle(User $user, ?int $branchId = null): Cart
    {
        $cart = Cart::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['branch_id' => $branchId],
        );

        if ($branchId !== null && $cart->branch_id !== $branchId) {
            $cart->update(['branch_id' => $branchId]);
        }

        return $cart->fresh(['items.addOns.addOn', 'items.product', 'items.variant', 'branch']);
    }
}
