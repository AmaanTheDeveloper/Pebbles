<?php

namespace App\Http\Controllers\Api\V1\Internal;

use App\Actions\RejectPayment;
use App\Actions\VerifyPayment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\RejectPaymentRequest;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Http\Resources\Order\CustomerOrderResource;
use App\Models\Order;

class PaymentVerificationController extends Controller
{
    public function verify(
        VerifyPaymentRequest $request,
        Order $order,
        VerifyPayment $verifyPayment,
    ): CustomerOrderResource {
        abort_unless($order->payment !== null, 422, 'No payment record exists for this order.');
        $this->authorize('verify', $order->payment);

        $verifyPayment->handle(
            $order,
            $request->user(),
            $request->validated('note'),
        );

        return new CustomerOrderResource($order->fresh([
            'items.addOns',
            'payment.proofs',
            'statusHistories',
            'branch',
        ]));
    }

    public function reject(
        RejectPaymentRequest $request,
        Order $order,
        RejectPayment $rejectPayment,
    ): CustomerOrderResource {
        abort_unless($order->payment !== null, 422, 'No payment record exists for this order.');
        $this->authorize('reject', $order->payment);

        $rejectPayment->handle(
            $order,
            $request->user(),
            $request->validated('reason'),
        );

        return new CustomerOrderResource($order->fresh([
            'items.addOns',
            'payment.proofs',
            'statusHistories',
            'branch',
        ]));
    }
}
