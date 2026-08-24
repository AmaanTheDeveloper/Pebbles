<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectPayment
{
    public function handle(Order $order, User $verifier, ?string $reason = null): Payment
    {
        $payment = $order->payment;

        if ($payment === null) {
            throw ValidationException::withMessages([
                'payment' => 'No payment record exists for this order.',
            ]);
        }

        if (! $payment->canBeRejected()) {
            throw ValidationException::withMessages([
                'payment' => 'Only submitted payments can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($payment, $verifier, $reason): Payment {
            $payment->update([
                'status' => PaymentStatus::Rejected,
                'verified_at' => now(),
                'verified_by' => $verifier->id,
                'rejection_reason' => $reason ?: 'Payment proof rejected.',
                'paid_at' => null,
            ]);

            return $payment->fresh('proofs');
        });
    }
}
