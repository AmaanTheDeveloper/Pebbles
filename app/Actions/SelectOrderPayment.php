<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentMethodCatalog;
use Illuminate\Validation\ValidationException;

class SelectOrderPayment
{
    public function __construct(
        private PaymentMethodCatalog $paymentMethods,
    ) {}

    public function handle(Order $order, PaymentMethod $method, ?string $transactionReference = null): Payment
    {
        if ($order->status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'order' => 'This order can no longer accept payment changes.',
            ]);
        }

        if (! $this->paymentMethods->isEnabled($method)) {
            throw ValidationException::withMessages([
                'method' => 'The selected payment method is not available.',
            ]);
        }

        $payment = $order->payment;

        if ($payment === null) {
            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'method' => $method,
                'status' => PaymentStatus::Pending,
                'amount' => $order->grand_total,
                'transaction_reference' => $transactionReference,
            ]);

            return $payment->fresh('proofs');
        }

        if (! $payment->canChangeMethod() && $payment->method !== $method) {
            throw ValidationException::withMessages([
                'method' => 'Payment method cannot be changed in the current payment state.',
            ]);
        }

        if ($payment->status === PaymentStatus::Verified) {
            throw ValidationException::withMessages([
                'method' => 'Verified payments cannot be changed.',
            ]);
        }

        $payment->update([
            'method' => $method,
            'status' => PaymentStatus::Pending,
            'transaction_reference' => $transactionReference ?? $payment->transaction_reference,
            'rejection_reason' => null,
            'verified_at' => null,
            'verified_by' => null,
            'paid_at' => null,
        ]);

        return $payment->fresh('proofs');
    }
}
