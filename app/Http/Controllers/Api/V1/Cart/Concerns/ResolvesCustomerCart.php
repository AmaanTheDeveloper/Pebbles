<?php

namespace App\Http\Controllers\Api\V1\Cart\Concerns;

use App\Models\Cart;
use App\Models\User;
use App\Support\GuestCartCookie;
use Illuminate\Http\Request;

trait ResolvesCustomerCart
{
    protected function cartUser(Request $request): ?User
    {
        /** @var User|null $user */
        $user = $request->user('web') ?? $request->user();

        return $user;
    }

    protected function guestTokenFor(Request $request, ?User $user): ?string
    {
        if ($user !== null) {
            return null;
        }

        return GuestCartCookie::read($request);
    }

    protected function authorizeCustomerCart(Request $request, Cart $cart, ?User $user, string $ability): void
    {
        if ($user !== null) {
            $this->authorize($ability, $cart);

            return;
        }

        abort_unless(is_string($cart->guest_token) && $cart->guest_token !== '', 403);

        $token = GuestCartCookie::read($request);

        if ($token !== null) {
            abort_unless(hash_equals($cart->guest_token, $token), 403);
        }
    }

    protected function authorizeGuestOwnedCart(Request $request, Cart $cart): void
    {
        $token = GuestCartCookie::read($request);

        abort_unless(
            is_string($cart->guest_token)
            && $cart->guest_token !== ''
            && is_string($token)
            && hash_equals($cart->guest_token, $token),
            403,
        );
    }
}
