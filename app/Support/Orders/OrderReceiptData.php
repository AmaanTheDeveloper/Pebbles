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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class OrderReceiptData
{
    /**
     * Build a historical, customer-safe receipt payload from stored order data.
     *
     * @return array<string, mixed>
     */
    public static function for(Order $order): array
    {
        $order->loadMissing([
            'items.addOns',
            'payment',
            'branch',
            'user',
            'statusHistories.changedBy',
        ]);

        $contact = ContactSettings::publicPayload();
        $timezone = ContactSettings::timezone();
        $branch = $order->branch;
        $payment = $order->payment;
        $logoSrc = self::logoDataUri();
        $orderedAt = self::inTimezone($order->created_at, $timezone);

        return [
            'app_name' => (string) config('app.name', 'Pebbles'),
            'website_url' => rtrim((string) config('app.url'), '/'),
            'document_title' => 'Order Receipt / Invoice',
            'logo_src' => $logoSrc,
            'has_logo' => $logoSrc !== null,
            'order_number' => (string) $order->public_order_number,
            'filename' => 'Pebbles-Order-'.preg_replace('/[^A-Za-z0-9\-]+/', '-', (string) $order->public_order_number).'.pdf',
            'ordered_at_label' => $orderedAt?->format('d M Y, g:i A'),
            'ordered_date_label' => $orderedAt?->format('d M Y'),
            'status_label' => CustomerOrderLifecycle::customerStatusLabel($order),
            'is_cancelled' => $order->status?->value === 'cancelled',
            'cancellation_label' => $order->status?->value === 'cancelled'
                ? CustomerOrderLifecycle::customerStatusLabel($order)
                : null,
            'order_type_label' => self::orderTypeLabel($order->type),
            'is_delivery' => $order->type === OrderType::Delivery,
            'is_takeaway' => $order->type === OrderType::Takeaway,
            'branch_name' => $branch?->name,
            'branch_address' => $branch?->address,
            'branch_phone' => $branch?->phone,
            'branch_whatsapp' => $branch?->whatsapp,
            'customer' => [
                'name' => $order->delivery_recipient_name
                    ?: ($order->user?->name ?? null),
                'phone' => $order->delivery_phone
                    ?: ($order->user?->phone ?? null),
                'email' => $order->user?->email,
            ],
            'delivery_zone_name' => $order->delivery_zone_name,
            'delivery_address_lines' => self::deliveryAddressLines($order),
            'pickup_notes' => $order->pickup_notes,
            'customer_notes' => $order->customer_notes,
            'items' => self::items($order),
            'subtotal' => self::money($order->subtotal),
            'discount_amount' => self::money($order->discount_amount),
            'has_discount' => (float) $order->discount_amount > 0,
            'offer_discount_amount' => self::money(max(0, (float) $order->discount_amount - (float) $order->coupon_discount_amount)),
            'has_offer_discount' => ((float) $order->discount_amount - (float) $order->coupon_discount_amount) > 0,
            'coupon_code' => $order->coupon_code,
            'coupon_discount_amount' => self::money($order->coupon_discount_amount),
            'has_coupon' => filled($order->coupon_code) && (float) $order->coupon_discount_amount > 0,
            'delivery_fee' => self::money($order->delivery_fee),
            'has_delivery_fee' => $order->type === OrderType::Delivery && (float) $order->delivery_fee > 0,
            'tax_amount' => self::money($order->tax_amount),
            'has_tax' => (float) $order->tax_amount > 0,
            'grand_total' => self::money($order->grand_total),
            'payment_method_label' => self::paymentMethodLabel($payment?->method),
            'payment_status_label' => self::paymentStatusLabel($payment?->method, $payment?->status),
            'track_url' => CustomerOrderLifecycle::isActive($order)
                ? url('/orders/'.$order->public_order_number.'/track')
                : url('/order/'.$order->public_order_number),
            'show_track_link' => CustomerOrderLifecycle::isActive($order),
            'contact_phone' => $contact['phone'] ?? null,
            'contact_whatsapp' => $contact['whatsapp'] ?? null,
            'contact_email' => $contact['email'] ?? null,
            'contact_address' => $contact['address'] ?? null,
            'generated_at_label' => now($timezone)->format('d M Y, g:i A'),
        ];
    }

    /**
     * @return list<array{
     *     name: string,
     *     quantity: int,
     *     unit_price: string,
     *     line_total: string,
     *     add_ons: list<array{name: string, quantity: int, unit_price: string, line_total: string}>
     * }>
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
                'name' => $name !== '' ? $name : 'Item',
                'quantity' => (int) $item->quantity,
                'unit_price' => self::money($item->unit_price),
                'line_total' => self::money($item->line_total),
                'add_ons' => $item->addOns->map(fn ($addOn): array => [
                    'name' => (string) $addOn->add_on_name,
                    'quantity' => (int) $addOn->quantity,
                    'unit_price' => self::money($addOn->unit_price),
                    'line_total' => self::money($addOn->line_total),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @return list<string>
     */
    private static function deliveryAddressLines(Order $order): array
    {
        if ($order->type !== OrderType::Delivery) {
            return [];
        }

        $lines = array_filter([
            $order->delivery_line1,
            $order->delivery_line2,
            collect([$order->delivery_area, $order->delivery_city])->filter()->implode(', ') ?: null,
        ], fn (?string $line): bool => filled($line));

        return array_values($lines);
    }

    private static function logoDataUri(): ?string
    {
        $path = BrandingSettings::logoPath() ?: BrandingSettings::faviconPath();

        if ($path === null || $path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            $fallbackPath = BrandingSettings::faviconPath();
            if ($fallbackPath !== null && $fallbackPath !== '' && Storage::disk('public')->exists($fallbackPath)) {
                $path = $fallbackPath;
            } else {
                return null;
            }
        }

        try {
            $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
            $contents = Storage::disk('public')->get($path);
        } catch (\Throwable) {
            return null;
        }

        if ($contents === false || $contents === '' || $contents === null) {
            return null;
        }

        if (in_array($mime, ['image/jpeg', 'image/jpg', 'image/svg+xml'], true)) {
            return 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        if (extension_loaded('gd')) {
            try {
                $img = @imagecreatefromstring($contents);
                if ($img !== false) {
                    $width = imagesx($img);
                    $height = imagesy($img);
                    $target = imagecreatetruecolor($width, $height);
                    $white = imagecolorallocate($target, 255, 255, 255);
                    imagefill($target, 0, 0, $white);
                    imagecopy($target, $img, 0, 0, 0, 0, $width, $height);

                    ob_start();
                    imagejpeg($target, null, 95);
                    $jpegBytes = ob_get_clean();

                    if ($jpegBytes !== false && $jpegBytes !== '') {
                        return 'data:image/jpeg;base64,'.base64_encode($jpegBytes);
                    }
                }
            } catch (\Throwable) {
                // Fall back to direct PNG encoding below
            }

            return 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        if (extension_loaded('imagick')) {
            return 'data:'.$mime.';base64,'.base64_encode($contents);
        }

        if (Storage::disk('public')->exists('branding/logo_pdf.jpg')) {
            $jpegContents = Storage::disk('public')->get('branding/logo_pdf.jpg');
            if ($jpegContents) {
                return 'data:image/jpeg;base64,'.base64_encode($jpegContents);
            }
        }

        return null;
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
            return 'Cash on Delivery';
        }

        return match ($status) {
            PaymentStatus::Pending => 'Pending',
            PaymentStatus::Submitted => 'Pending Verification',
            PaymentStatus::Verified => 'Paid',
            PaymentStatus::Rejected => 'Rejected',
            PaymentStatus::Failed => 'Failed',
            PaymentStatus::Refunded => 'Refunded',
        };
    }

    private static function money(mixed $amount): string
    {
        return 'Rs. '.number_format((float) $amount, 0, '.', ',');
    }

    private static function inTimezone(mixed $value, string $timezone): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->timezone($timezone);
    }
}
