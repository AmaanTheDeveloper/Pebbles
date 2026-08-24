<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelCustomerOrder;
use App\Actions\ModifyCustomerOrder;
use App\Actions\ReorderOrderToCart;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelCustomerOrderRequest;
use App\Http\Requests\Order\ModifyCustomerOrderRequest;
use App\Http\Requests\Order\ReorderCustomerOrderRequest;
use App\Http\Resources\Cart\CartResource;
use App\Http\Resources\Order\CustomerOrderListResource;
use App\Http\Resources\Order\CustomerOrderResource;
use App\Models\Order;
use App\Support\CustomerOrderLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $validated = $request->validate([
            'scope' => ['sometimes', 'nullable', 'in:active,recent,all'],
            'recent_page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $scope = $validated['scope'] ?? 'all';
        $userId = $request->user()->id;
        $finalStatuses = array_map(fn (OrderStatus $status) => $status->value, CustomerOrderLifecycle::finalStatuses());

        if ($scope === 'active') {
            $active = Order::query()
                ->where('user_id', $userId)
                ->whereNotIn('status', $finalStatuses)
                ->with(['branch', 'payment'])
                ->latest()
                ->get();

            return response()->json([
                'data' => [
                    'active' => CustomerOrderListResource::collection($active),
                ],
            ]);
        }

        if ($scope === 'recent') {
            $recent = Order::query()
                ->where('user_id', $userId)
                ->whereIn('status', $finalStatuses)
                ->with(['branch', 'payment'])
                ->orderByRaw('COALESCE(cancelled_at, updated_at) DESC')
                ->paginate(10, ['*'], 'recent_page', $validated['recent_page'] ?? 1);

            return response()->json([
                'data' => [
                    'recent' => CustomerOrderListResource::collection($recent),
                ],
                'meta' => [
                    'recent' => [
                        'current_page' => $recent->currentPage(),
                        'last_page' => $recent->lastPage(),
                        'per_page' => $recent->perPage(),
                        'total' => $recent->total(),
                    ],
                ],
            ]);
        }

        $active = Order::query()
            ->where('user_id', $userId)
            ->whereNotIn('status', $finalStatuses)
            ->with(['branch', 'payment'])
            ->latest()
            ->get();

        $recent = Order::query()
            ->where('user_id', $userId)
            ->whereIn('status', $finalStatuses)
            ->with(['branch', 'payment'])
            ->orderByRaw('COALESCE(cancelled_at, updated_at) DESC')
            ->paginate(10, ['*'], 'recent_page', $validated['recent_page'] ?? 1);

        return response()->json([
            'data' => [
                'active' => CustomerOrderListResource::collection($active),
                'recent' => CustomerOrderListResource::collection($recent),
            ],
            'meta' => [
                'recent' => [
                    'current_page' => $recent->currentPage(),
                    'last_page' => $recent->lastPage(),
                    'per_page' => $recent->perPage(),
                    'total' => $recent->total(),
                ],
            ],
        ]);
    }

    public function show(Order $order): CustomerOrderResource
    {
        $this->authorize('view', $order);

        $order->load([
            'items.addOns',
            'payment.proofs',
            'statusHistories.changedBy',
            'branch',
        ]);

        return new CustomerOrderResource($order);
    }

    public function statusHistory(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['statusHistories']);

        return response()->json([
            'data' => $order->statusHistories
                ->sortBy('created_at')
                ->values()
                ->map(fn ($history) => [
                    'from_status' => $history->from_status?->value,
                    'status' => $history->to_status?->value,
                    'created_at' => $history->created_at,
                ]),
        ]);
    }

    public function cancel(
        CancelCustomerOrderRequest $request,
        Order $order,
        CancelCustomerOrder $cancelCustomerOrder,
    ): JsonResponse {
        $this->authorize('cancel', $order);

        $updated = $cancelCustomerOrder->handle(
            $order,
            $request->user(),
            $request->resolvedReason(),
        );

        $updated->load([
            'items.addOns',
            'payment.proofs',
            'statusHistories.changedBy',
            'branch',
        ]);

        return response()->json([
            'data' => new CustomerOrderResource($updated),
            'message' => 'Order cancelled successfully.',
        ]);
    }

    public function previewModify(
        ModifyCustomerOrderRequest $request,
        Order $order,
        ModifyCustomerOrder $modifyCustomerOrder,
    ): JsonResponse {
        $this->authorize('modify', $order);

        $result = $modifyCustomerOrder->handle(
            $order,
            $request->user(),
            $request->validated(),
            preview: true,
        );

        return response()->json([
            'data' => [
                'previous_total' => $result['previous_total'],
                'new_total' => $result['new_total'],
                'order' => new CustomerOrderResource($result['order']),
            ],
        ]);
    }

    public function modify(
        ModifyCustomerOrderRequest $request,
        Order $order,
        ModifyCustomerOrder $modifyCustomerOrder,
    ): JsonResponse {
        $this->authorize('modify', $order);

        $result = $modifyCustomerOrder->handle(
            $order,
            $request->user(),
            $request->validated(),
        );

        $result['order']->load([
            'items.addOns',
            'payment.proofs',
            'statusHistories.changedBy',
            'branch',
        ]);

        return response()->json([
            'data' => new CustomerOrderResource($result['order']),
            'message' => 'Your order has been updated successfully.',
        ]);
    }

    public function reorder(
        ReorderCustomerOrderRequest $request,
        Order $order,
        ReorderOrderToCart $reorderOrderToCart,
    ): JsonResponse {
        $this->authorize('view', $order);

        $result = $reorderOrderToCart->handle(
            $order,
            $request->user(),
            $request->mode(),
        );

        $message = $result['unavailable'] === []
            ? 'Items added to your cart.'
            : 'Some items from this order are no longer available. Available items were added to your cart.';

        return response()->json([
            'data' => [
                'cart' => new CartResource($result['cart']),
                'summary' => [
                    'added' => $result['added'],
                    'unavailable' => $result['unavailable'],
                    'added_count' => count($result['added']),
                    'unavailable_count' => count($result['unavailable']),
                ],
            ],
            'message' => $message,
        ]);
    }

    public function activeStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $finalStatuses = array_map(
            fn (OrderStatus $status) => $status->value,
            CustomerOrderLifecycle::finalStatuses(),
        );

        $hasActiveOrders = Order::query()
            ->where('user_id', $request->user()->id)
            ->whereNotIn('status', $finalStatuses)
            ->exists();

        return response()->json([
            'data' => [
                'has_active_orders' => $hasActiveOrders,
            ],
        ]);
    }
}
