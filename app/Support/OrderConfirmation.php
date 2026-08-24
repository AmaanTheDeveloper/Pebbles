<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Carbon;

class OrderConfirmation
{
    public static function isConfirmed(Order $order): bool
    {
        return self::confirmedAt($order) !== null;
    }

    public static function confirmedAt(Order $order): ?Carbon
    {
        if ($order->confirmed_at !== null) {
            return $order->confirmed_at;
        }

        if ($order->status === OrderStatus::Pending) {
            return null;
        }

        if ($order->relationLoaded('statusHistories')) {
            $historyConfirmedAt = $order->statusHistories
                ->where('to_status', OrderStatus::Confirmed)
                ->sortBy('created_at')
                ->first()
                ?->created_at;

            if ($historyConfirmedAt !== null) {
                return $historyConfirmedAt;
            }
        }

        if ($order->status !== null && $order->status !== OrderStatus::Pending) {
            return $order->created_at;
        }

        return null;
    }
}
