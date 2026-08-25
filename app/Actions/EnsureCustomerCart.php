<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\User;
use App\Support\GuestCartCookie;

class EnsureCustomerCart
{
    public function handle(?User $user = null, ?int $branchId = null, ?string $guestToken = null): Cart
    {
        if ($user !== null) {
            $cart = Cart::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'branch_id' => $branchId,
                    'guest_token' => null,
                ],
            );

            $updates = [];

            if ($cart->guest_token !== null) {
                $updates['guest_token'] = null;
            }

            if ($branchId !== null && $cart->branch_id !== $branchId) {
                $updates['branch_id'] = $branchId;
            }

            if ($updates !== []) {
                $cart->update($updates);
            }

            return $cart->fresh(['items.addOns.addOn', 'items.product', 'items.variant', 'branch']);
        }

        $token = is_string($guestToken) && $guestToken !== ''
            ? $guestToken
            : GuestCartCookie::issue();

        $cart = Cart::query()->firstOrCreate(
            ['guest_token' => $token],
            [
                'user_id' => null,
                'branch_id' => $branchId,
            ],
        );

        if ($branchId !== null && $cart->branch_id !== $branchId) {
            $cart->update(['branch_id' => $branchId]);
        }

        return $cart->fresh(['items.addOns.addOn', 'items.product', 'items.variant', 'branch']);
    }
}
