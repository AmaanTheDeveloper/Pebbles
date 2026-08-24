<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        $order = $payment->relationLoaded('order') ? $payment->order : $payment->order()->first();

        if ($order !== null && $order->user_id === $user->id) {
            return true;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isManager()
            && $order !== null
            && $user->belongsToBranch($order->branch_id);
    }

    public function update(User $user, Payment $payment): bool
    {
        $order = $payment->relationLoaded('order') ? $payment->order : $payment->order()->first();

        return $order !== null && $order->user_id === $user->id;
    }

    public function uploadProof(User $user, Payment $payment): bool
    {
        return $this->update($user, $payment);
    }

    public function downloadProof(User $user, PaymentProof $proof): bool
    {
        $payment = $proof->payment;

        if ($payment === null) {
            return false;
        }

        return $this->view($user, $payment);
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $this->canManageBranchPayment($user, $payment);
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $this->canManageBranchPayment($user, $payment);
    }

    private function canManageBranchPayment(User $user, Payment $payment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $order = $payment->relationLoaded('order') ? $payment->order : $payment->order()->first();

        return $user->isManager()
            && $order !== null
            && $user->belongsToBranch($order->branch_id);
    }
}
