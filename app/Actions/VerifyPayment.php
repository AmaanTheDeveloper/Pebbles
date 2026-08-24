<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyPayment
{
    public function handle(Order $order, User $verifier, ?string $note = null): Payment
    {
        $payment = $order->payment;

        if ($payment === null) {
            throw ValidationException::withMessages([
                'payment' => 'No payment record exists for this order.',
            ]);
        }

        if (! $payment->canBeVerified()) {
            throw ValidationException::withMessages([
                'payment' => 'Only submitted payments (or pending COD) can be verified.',
            ]);
        }

        if ($order->status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'order' => 'Cancelled orders cannot have payments verified.',
            ]);
        }

        return DB::transaction(function () use ($payment, $verifier): Payment {
            $payment->update([
                'status' => PaymentStatus::Verified,
                'verified_at' => now(),
                'verified_by' => $verifier->id,
                'paid_at' => now(),
                'rejection_reason' => null,
            ]);

            return $payment->fresh('proofs');
        });
    }
}
