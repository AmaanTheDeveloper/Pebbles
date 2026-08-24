<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderListResource;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Order;
use App\Support\AdminBranchScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['sometimes', 'nullable', Rule::enum(PaymentStatus::class)],
            'type' => ['sometimes', 'nullable', Rule::enum(OrderType::class)],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'search' => ['sometimes', 'nullable', 'string', 'max:50'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'sort' => ['sometimes', 'nullable', Rule::in(['newest', 'oldest', 'highest_total'])],
        ]);

        $query = Order::query()->with(['user', 'branch', 'payment']);

        AdminBranchScope::constrain($query, $request->user(), $validated['branch_id'] ?? null);

        $query
            ->when(
                filled($validated['status'] ?? null),
                fn ($q) => $q->where('status', $validated['status'])
            )
            ->when(
                filled($validated['type'] ?? null),
                fn ($q) => $q->where('type', $validated['type'])
            )
            ->when(
                filled($validated['payment_status'] ?? null),
                fn ($q) => $q->whereHas('payment', fn ($payment) => $payment->where('status', $validated['payment_status']))
            )
            ->when(
                filled($validated['search'] ?? null),
                fn ($q) => $q->where('public_order_number', 'like', '%'.$validated['search'].'%')
            )
            ->when(
                filled($validated['date_from'] ?? null),
                fn ($q) => $q->whereDate('created_at', '>=', $validated['date_from'])
            )
            ->when(
                filled($validated['date_to'] ?? null),
                fn ($q) => $q->whereDate('created_at', '<=', $validated['date_to'])
            );

        $sort = $validated['sort'] ?? 'newest';

        $query = match ($sort) {
            'oldest' => $query->oldest(),
            'highest_total' => $query->orderByDesc('grand_total'),
            default => $query->latest(),
        };

        return AdminOrderListResource::collection($query->paginate(20));
    }

    public function show(Request $request, Order $order): AdminOrderResource
    {
        $this->authorize('view', $order);
        AdminBranchScope::authorizeOrderAccess($request->user(), $order->branch_id);

        $order->load([
            'user',
            'branch',
            'items.addOns',
            'payment.proofs',
            'statusHistories.changedBy',
        ]);

        return new AdminOrderResource($order);
    }

    public function updateStatus(Request $request, Order $order, UpdateOrderStatus $updateOrderStatus): AdminOrderResource
    {
        $this->authorize('update', $order);
        AdminBranchScope::authorizeOrderAccess($request->user(), $order->branch_id);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $updateOrderStatus->handle(
            $order,
            OrderStatus::from($validated['status']),
            $request->user(),
            $validated['note'] ?? null,
        );

        return new AdminOrderResource($updated);
    }
}
