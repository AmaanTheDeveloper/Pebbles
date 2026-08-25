<?php

namespace App\Support;

use App\Enums\OrderType;
use App\Models\Branch;

class OrderEstimateResolver
{
    /**
     * Resolve ETA minutes for an order from the selected branch and fulfillment type.
     *
     * Delivery uses preparation_minutes + delivery_minutes.
     * Takeaway and dine-in use preparation_minutes only.
     * Falls back to the global OrderEstimateSettings value when branch minutes are null/0.
     */
    public static function minutesFor(Branch $branch, OrderType $type): int
    {
        $preparation = (int) ($branch->preparation_minutes ?? 0);
        $delivery = (int) ($branch->delivery_minutes ?? 0);

        if ($type === OrderType::Delivery) {
            if ($preparation <= 0 || $delivery <= 0) {
                return OrderEstimateSettings::minutes();
            }

            return self::clamp($preparation + $delivery);
        }

        if ($preparation <= 0) {
            return OrderEstimateSettings::minutes();
        }

        return self::clamp($preparation);
    }

    private static function clamp(int $minutes): int
    {
        return max(5, min(240, $minutes));
    }
}
