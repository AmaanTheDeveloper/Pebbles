<?php

namespace App\Support\Orders;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Support\BrandingSettings;
use App\Support\ContactSettings;
use App\Support\CustomerOrderLifecycle;
use App\Support\PaymentMethodCatalog;
use Illuminate\Support\Collection;

class OrderConfirmedMailData
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Order $order): array
    {
        $order->loadMissing(['items.addOns', 'payment', 'branch', 'user']);

        $contact = ContactSettings::publicPayload();
        $branding = BrandingSettings::publicPayload();
        $payment = $order->payment;
        $branch = $order->branch;
        $customerName = trim((string) ($order->user?->name ?? ''));
        $firstName = $customerName !== ''
            ? (explode(' ', $customerName)[0] ?? $customerName)
            : null;

        return [
            'app_name' => (string) config('app.name', 'Pebbles'),
            'preheader' => 'Your order has been confirmed and is now being prepared.',
            'logo_url' => $branding['logo']['url'] ?? null,
            'greeting_name' => $firstName ?: 'there',
            'order_number' => (string) $order->public_order_number,
            'status_label' => CustomerOrderLifecycle::customerStatusLabel($order),
            'branch_name' => $branch?->name,
            'branch_address' => $branch?->address,
            'order_type_label' => self::orderTypeLabel($order->type),
            'is_delivery' => $order->type === OrderType::Delivery,
            'is_takeaway' => $order->type === OrderType::Takeaway,
            'delivery_address' => self::deliveryAddress($order),
            'estimated_minutes' => $order->estimated_minutes,
            'items' => self::items($order),
            'subtotal' => self::money($order->subtotal),
            'discount_amount' => self::money($order->discount_amount),
            'has_discount' => (float) $order->discount_amount > 0,
            'delivery_fee' => self::money($order->delivery_fee),
            'has_delivery_fee' => (float) $order->delivery_fee > 0,
            'grand_total' => self::money($order->grand_total),
            'payment_method_label' => self::paymentMethodLabel($payment?->method),
            'payment_status_label' => self::paymentStatusLabel($payment?->method, $payment?->status),
            'track_url' => url('/orders/'.$order->public_order_number.'/track'),
            'contact_email' => $contact['email'] ?? null,
            'contact_phone' => $contact['phone'] ?? null,
            'social' => is_array($contact['social'] ?? null) ? $contact['social'] : [],
            'year' => now()->year,
        ];
    }

    /**
     * @return list<array{name: string, quantity: int, line_total: string, add_ons: list<array{name: string, quantity: int}>}>
     */
    private static function items(Order $order): array
    {
        /** @var Collection<int, mixed> $items */
        $items = $order->items;

        return $items->map(function ($item): array {
            $name = trim((string) $item->product_name);
            if (filled($item->variant_name)) {
                $name .= ' ('.$item->variant_name.')';
            }

            return [
                'name' => $name,
                'quantity' => (int) $item->quantity,
                'line_total' => self::money($item->line_total),
                'add_ons' => $item->addOns->map(fn ($addOn): array => [
                    'name' => (string) $addOn->add_on_name,
                    'quantity' => (int) $addOn->quantity,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    private static function deliveryAddress(Order $order): ?string
    {
        if ($order->type !== OrderType::Delivery) {
            return null;
        }

        $parts = array_filter([
            $order->delivery_recipient_name,
            $order->delivery_line1,
            $order->delivery_line2,
            $order->delivery_area,
            $order->delivery_city,
            $order->delivery_phone ? 'Phone: '.$order->delivery_phone : null,
        ], fn (?string $part): bool => filled($part));

        return $parts === [] ? null : implode(', ', $parts);
    }

    private static function orderTypeLabel(?OrderType $type): string
    {
        return match ($type) {
            OrderType::Delivery => 'Delivery',
            OrderType::Takeaway => 'Takeaway',
            OrderType::DineIn => 'Dine In',
            default => 'Order',
        };
    }

    private static function paymentMethodLabel(?PaymentMethod $method): ?string
    {
        if ($method === null) {
            return null;
        }

        try {
            return app(PaymentMethodCatalog::class)->get($method)['label'];
        } catch (\InvalidArgumentException) {
            return ucfirst(str_replace('_', ' ', $method->value));
        }
    }

    private static function paymentStatusLabel(?PaymentMethod $method, ?PaymentStatus $status): ?string
    {
        if ($status === null) {
            return null;
        }

        if ($method === PaymentMethod::Cod && in_array($status, [PaymentStatus::Pending, PaymentStatus::Submitted], true)) {
            return 'COD';
        }

        return match ($status) {
            PaymentStatus::Pending => 'Pending',
            PaymentStatus::Submitted => 'Pending Verification',
            PaymentStatus::Verified => 'Approved',
            PaymentStatus::Rejected => 'Rejected',
            PaymentStatus::Failed => 'Failed',
            PaymentStatus::Refunded => 'Refunded',
        };
    }

    private static function money(mixed $amount): string
    {
        return 'Rs. '.number_format((float) $amount, 0, '.', ',');
    }
}
