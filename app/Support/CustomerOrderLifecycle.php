<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use Illuminate\Support\Carbon;

class CustomerOrderLifecycle
{
    /**
     * @return list<OrderStatus>
     */
    public static function finalStatuses(): array
    {
        return [
            OrderStatus::Completed,
            OrderStatus::Cancelled,
        ];
    }

    public static function isFinal(Order $order): bool
    {
        return $order->status !== null
            && in_array($order->status, self::finalStatuses(), true);
    }

    public static function isActive(Order $order): bool
    {
        return ! self::isFinal($order);
    }

    public static function finalizedAt(Order $order): ?Carbon
    {
        if (! self::isFinal($order)) {
            return null;
        }

        if ($order->status === OrderStatus::Cancelled) {
            return $order->cancelled_at ?? $order->updated_at;
        }

        if ($order->relationLoaded('statusHistories')) {
            $completedAt = $order->statusHistories
                ->where('to_status', OrderStatus::Completed)
                ->sortByDesc('created_at')
                ->first()
                ?->created_at;

            if ($completedAt !== null) {
                return $completedAt;
            }
        }

        return $order->updated_at;
    }

    public static function cancellationSource(Order $order): ?string
    {
        if ($order->status !== OrderStatus::Cancelled) {
            return null;
        }

        if ($order->relationLoaded('statusHistories')) {
            $history = $order->statusHistories
                ->where('to_status', OrderStatus::Cancelled)
                ->sortByDesc('created_at')
                ->first();

            if ($history !== null) {
                $note = strtolower(trim((string) ($history->note ?? '')));

                if (str_contains($note, 'cancelled by customer')) {
                    return 'customer';
                }

                if ($history->relationLoaded('changedBy') && $history->changedBy?->isSuperAdmin()) {
                    return 'admin';
                }

                if ($history->changed_by !== null && $history->changed_by === $order->user_id) {
                    return 'customer';
                }
            }
        }

        $reason = strtolower(trim((string) ($order->cancellation_reason ?? '')));

        if (str_contains($reason, 'cancelled by customer')) {
            return 'customer';
        }

        $customerReasons = [
            'ordered by mistake',
            'need to change something',
            'changed my mind',
            'other',
        ];

        if (in_array($reason, $customerReasons, true)) {
            return 'customer';
        }

        return 'admin';
    }

    public static function customerStatusLabel(Order $order): string
    {
        if ($order->status === OrderStatus::Cancelled) {
            return self::cancellationSource($order) === 'customer'
                ? 'Cancelled by Customer'
                : 'Cancelled by Admin';
        }

        if ($order->status === OrderStatus::Completed) {
            return $order->type === OrderType::Delivery ? 'Delivered' : 'Picked Up';
        }

        return match ($order->status) {
            OrderStatus::Pending => 'Order Received',
            OrderStatus::Confirmed => 'Confirmed',
            OrderStatus::Preparing => 'Preparing',
            OrderStatus::Ready => $order->type === OrderType::Delivery ? 'Ready' : 'Ready for Pickup',
            OrderStatus::OutForDelivery => 'Out for Delivery',
            default => ucfirst(str_replace('_', ' ', (string) $order->status?->value)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function payloadFor(Order $order): array
    {
        $isFinal = self::isFinal($order);

        return [
            'is_active' => ! $isFinal,
            'is_final' => $isFinal,
            'customer_status_label' => self::customerStatusLabel($order),
            'cancellation_source' => self::cancellationSource($order),
            'cancellation_reason' => $order->cancellation_reason,
            'finalized_at' => self::finalizedAt($order)?->toIso8601String(),
        ];
    }
}
