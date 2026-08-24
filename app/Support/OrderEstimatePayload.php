<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Carbon;

class OrderEstimatePayload
{
    /**
     * @return array<string, mixed>|null
     */
    public static function for(Order $order): ?array
    {
        if ($order->estimated_minutes === null) {
            return null;
        }

        $startsAt = self::startsAtFor($order);

        if ($startsAt === null) {
            return null;
        }

        $deadline = $startsAt->copy()->addMinutes((int) $order->estimated_minutes);

        return [
            'minutes' => (int) $order->estimated_minutes,
            'starts_at' => $startsAt->toIso8601String(),
            'deadline_at' => $deadline->toIso8601String(),
            'server_now' => now()->toIso8601String(),
        ];
    }

    public static function startsAtFor(Order $order): ?Carbon
    {
        return OrderConfirmation::confirmedAt($order);
    }

    public static function deadlineFor(Order $order): ?Carbon
    {
        $startsAt = self::startsAtFor($order);

        if ($startsAt === null || $order->estimated_minutes === null) {
            return null;
        }

        return $startsAt->copy()->addMinutes((int) $order->estimated_minutes);
    }
}
