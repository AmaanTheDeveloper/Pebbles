<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\RejectPayment;
use App\Actions\VerifyPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\RejectPaymentRequest;
use App\Http\Requests\Payment\VerifyPaymentRequest;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Http\Resources\Admin\AdminPaymentListResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Support\AdminBranchScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', Rule::enum(PaymentStatus::class)],
            'method' => ['sometimes', 'nullable', Rule::enum(PaymentMethod::class)],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
        ]);

        $query = Payment::query()->with(['order.user', 'order.branch', 'proofs']);

        AdminBranchScope::constrainPayments($query, $request->user(), $validated['branch_id'] ?? null);

        $payments = $query
            ->when(
                filled($validated['status'] ?? null),
                fn ($q) => $q->where('status', $validated['status'])
            )
            ->when(
                filled($validated['method'] ?? null),
                fn ($q) => $q->where('method', $validated['method'])
            )
            ->when(
                filled($validated['date_from'] ?? null),
                fn ($q) => $q->whereDate('created_at', '>=', $validated['date_from'])
            )
            ->when(
                filled($validated['date_to'] ?? null),
                fn ($q) => $q->whereDate('created_at', '<=', $validated['date_to'])
            )
            ->latest()
            ->paginate(20);

        return AdminPaymentListResource::collection($payments);
    }

    public function show(Request $request, Payment $payment): JsonResource
    {
        $payment->load(['order.user', 'order.branch', 'proofs']);
        $this->authorize('view', $payment);
        AdminBranchScope::authorizeOrderAccess($request->user(), $payment->order?->branch_id);

        return new AdminPaymentListResource($payment);
    }

    public function verify(VerifyPaymentRequest $request, Order $order, VerifyPayment $verifyPayment): AdminOrderResource
    {
        abort_unless($order->payment !== null, 422);
        AdminBranchScope::authorizeOrderAccess($request->user(), $order->branch_id);
        $this->authorize('verify', $order->payment);

        $verifyPayment->handle($order, $request->user(), $request->validated('note'));

        return new AdminOrderResource($order->fresh([
            'user',
            'branch',
            'items.addOns',
            'payment.proofs',
            'statusHistories.changedBy',
        ]));
    }

    public function reject(RejectPaymentRequest $request, Order $order, RejectPayment $rejectPayment): AdminOrderResource
    {
        abort_unless($order->payment !== null, 422);
        AdminBranchScope::authorizeOrderAccess($request->user(), $order->branch_id);
        $this->authorize('reject', $order->payment);

        $rejectPayment->handle($order, $request->user(), $request->validated('reason'));

        return new AdminOrderResource($order->fresh([
            'user',
            'branch',
            'items.addOns',
            'payment.proofs',
            'statusHistories.changedBy',
        ]));
    }

    public function showProof(Request $request, Order $order, PaymentProof $proof): StreamedResponse
    {
        abort_unless($order->payment !== null && $proof->payment_id === $order->payment->id, 404);
        AdminBranchScope::authorizeOrderAccess($request->user(), $order->branch_id);
        $this->authorize('view', $order->payment);

        abort_unless($proof->isStoredPrivately(), 404);
        abort_unless(Storage::disk($proof->disk)->exists($proof->path), 404);

        return Storage::disk($proof->disk)->response(
            $proof->path,
            $proof->original_filename ?: basename($proof->path),
            ['Content-Type' => $proof->mime_type ?: 'application/octet-stream'],
        );
    }
}
