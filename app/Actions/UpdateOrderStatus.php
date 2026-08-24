<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderConfirmed;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Support\OrderStatusTransitions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatus
{
    public function __construct(
        private OrderStatusTransitions $transitions,
    ) {}

    public function handle(Order $order, OrderStatus $to, User $actor, ?string $note = null): Order
    {
        $from = $order->status;

        if ($from === null || ! $this->transitions->canTransition($from, $to)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Cannot transition order from %s to %s.',
                    $from?->value ?? 'unknown',
                    $to->value,
                ),
            ]);
        }

        $becameConfirmed = $from !== OrderStatus::Confirmed && $to === OrderStatus::Confirmed;

        $updated = DB::transaction(function () use ($order, $from, $to, $actor, $note): Order {
            $updates = [
                'status' => $to,
                'cancelled_at' => $to === OrderStatus::Cancelled ? now() : $order->cancelled_at,
                'cancellation_reason' => $to === OrderStatus::Cancelled
                    ? ($note ?: $order->cancellation_reason)
                    : $order->cancellation_reason,
            ];

            if ($to === OrderStatus::Confirmed && $order->confirmed_at === null) {
                $updates['confirmed_at'] = now();
            }

            $order->update($updates);

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor->id,
                'note' => $note,
                'created_at' => now(),
            ]);

            return $order->fresh([
                'items.addOns',
                'payment.proofs',
                'statusHistories',
                'branch',
                'user',
            ]);
        });

        if ($becameConfirmed) {
            OrderConfirmed::dispatch($updated);
        }

        return $updated;
    }
}
