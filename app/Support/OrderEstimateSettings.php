<?php

namespace App\Support;

use App\Models\Setting;

class OrderEstimateSettings
{
    public static function minutes(): int
    {
        return max(5, min(240, (int) Setting::getValue(
            'order_estimated_minutes',
            config('pebbles.order_estimated_minutes', 30),
        )));
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        return [
            'order_estimated_minutes' => self::minutes(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function update(array $payload): void
    {
        if (! array_key_exists('order_estimated_minutes', $payload)) {
            return;
        }

        Setting::setValue(
            'order_estimated_minutes',
            max(5, min(240, (int) $payload['order_estimated_minutes'])),
            'integer',
        );
    }
}
