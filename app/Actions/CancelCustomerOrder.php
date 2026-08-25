<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderCancelledByCustomer;
use App\Models\Order;
use App\Models\User;
use App\Support\CustomerOrderActions;
use App\Support\ProductStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelCustomerOrder
{
    public function __construct(
        private UpdateOrderStatus $updateOrderStatus,
    ) {}

    public function handle(Order $order, User $user, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $user, $reason): Order {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! CustomerOrderActions::canCancel($locked)) {
                throw ValidationException::withMessages([
                    'order' => CustomerOrderActions::cancelUnavailableReason($locked)
                        ?? 'This order can no longer be cancelled because the allowed cancellation window has expired or the order is already being processed.',
                ]);
            }

            $note = trim((string) ($reason ?: 'Cancelled by customer.'));

            $locked->loadMissing('items');

            $quantitiesByProductId = $locked->items
                ->groupBy('product_id')
                ->map(fn ($items) => (int) $items->sum('quantity'))
                ->all();

            ProductStock::restore($quantitiesByProductId);

            $cancelled = $this->updateOrderStatus->handle(
                $locked,
                OrderStatus::Cancelled,
                $user,
                $note,
            );

            OrderCancelledByCustomer::dispatch($cancelled);

            return $cancelled;
        });
    }
}
