<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProofUploaded implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public Payment $payment,
        public PaymentProof $proof,
    ) {}
}
