<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isManager() && $user->belongsToBranch($order->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->isUser() || $user->canAccessAdmin();
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isManager() && $user->belongsToBranch($order->branch_id);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isSuperAdmin();
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($order->status === OrderStatus::Cancelled || $order->cancelled_at !== null) {
            return false;
        }

        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isManager() && $user->belongsToBranch($order->branch_id)) {
            return true;
        }

        return $order->user_id === $user->id;
    }

    public function modify(User $user, Order $order): bool
    {
        if ($order->status === OrderStatus::Cancelled || $order->cancelled_at !== null) {
            return false;
        }

        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $order->user_id === $user->id;
    }
}
