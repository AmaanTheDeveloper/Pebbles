<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

class CartPolicy
{
    public function view(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id;
    }

    public function update(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id;
    }

    public function delete(User $user, Cart $cart): bool
    {
        return $cart->user_id === $user->id;
    }
}
