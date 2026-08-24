<?php

namespace App\Support;

use App\Models\Setting;

class OrderChangeSettings
{
    public static function cancellationEnabled(): bool
    {
        return (bool) Setting::getValue('customer_order_cancellation_enabled', true);
    }

    public static function modificationEnabled(): bool
    {
        return (bool) Setting::getValue('customer_order_modification_enabled', true);
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        return [
            'customer_order_cancellation_enabled' => self::cancellationEnabled(),
            'customer_order_modification_enabled' => self::modificationEnabled(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function update(array $payload): void
    {
        if (array_key_exists('customer_order_cancellation_enabled', $payload)) {
            Setting::setValue(
                'customer_order_cancellation_enabled',
                (bool) $payload['customer_order_cancellation_enabled'],
                'boolean',
            );
        }

        if (array_key_exists('customer_order_modification_enabled', $payload)) {
            Setting::setValue(
                'customer_order_modification_enabled',
                (bool) $payload['customer_order_modification_enabled'],
                'boolean',
            );
        }
    }
}
