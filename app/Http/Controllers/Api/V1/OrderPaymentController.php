<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SelectOrderPayment;
use App\Actions\UploadPaymentProof;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\SelectOrderPaymentRequest;
use App\Http\Requests\Payment\StorePaymentProofRequest;
use App\Http\Resources\Order\CustomerOrderResource;
use App\Models\Order;
use App\Models\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderPaymentController extends Controller
{
    public function store(
        SelectOrderPaymentRequest $request,
        Order $order,
        SelectOrderPayment $selectOrderPayment,
    ): CustomerOrderResource {
        $this->authorize('view', $order);

        if ($order->payment) {
            $this->authorize('update', $order->payment);
        }

        $selectOrderPayment->handle(
            $order,
            PaymentMethod::from($request->validated('method')),
            $request->validated('transaction_reference'),
        );

        $order->load(['items.addOns', 'payment.proofs', 'statusHistories', 'branch']);

        return new CustomerOrderResource($order->fresh([
            'items.addOns',
            'payment.proofs',
            'statusHistories',
            'branch',
        ]));
    }

    public function storeProof(
        StorePaymentProofRequest $request,
        Order $order,
        UploadPaymentProof $uploadPaymentProof,
    ): CustomerOrderResource {
        $this->authorize('view', $order);
        abort_unless($order->payment !== null, 422, 'No payment record exists for this order.');
        $this->authorize('uploadProof', $order->payment);

        $uploadPaymentProof->handle(
            $order,
            $request->user(),
            $request->file('proof'),
            $request->validated('transaction_reference'),
        );

        return new CustomerOrderResource($order->fresh([
            'items.addOns',
            'payment.proofs',
            'statusHistories',
            'branch',
        ]));
    }

    public function showProof(Request $request, Order $order, PaymentProof $proof): StreamedResponse
    {
        $this->authorize('view', $order);

        $proof->load('payment.order');
        abort_unless(
            $order->payment !== null && $proof->payment_id === $order->payment->id,
            404
        );

        $this->authorize('view', $proof->payment);

        abort_unless($proof->isStoredPrivately(), 404);
        abort_unless(Storage::disk($proof->disk)->exists($proof->path), 404);

        return Storage::disk($proof->disk)->response(
            $proof->path,
            $proof->original_filename ?: basename($proof->path),
            [
                'Content-Type' => $proof->mime_type ?: 'application/octet-stream',
            ]
        );
    }
}
