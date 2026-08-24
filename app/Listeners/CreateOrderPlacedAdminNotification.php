<?php

namespace App\Listeners;

use App\Enums\AdminNotificationType;
use App\Enums\OrderType;
use App\Events\OrderPlaced;
use App\Support\AdminNotificationService;

class CreateOrderPlacedAdminNotification
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing(['branch', 'user', 'payment']);
        $orderNumber = $order->public_order_number;
        $amount = $this->notifications->formatMoney($order->grand_total);
        $branchName = $order->branch?->name;
        $customerName = $order->user?->name ?? 'A customer';
        $typeLabel = match ($order->type) {
            OrderType::Delivery => 'Delivery',
            OrderType::Takeaway => 'Takeaway',
            OrderType::DineIn => 'Dine-in',
            default => 'Order',
        };
        $paymentMethod = $order->payment?->method?->value;

        $messageParts = [
            "{$customerName} placed a new {$typeLabel} order worth {$amount}.",
        ];

        if ($branchName) {
            $messageParts[] = "Branch: {$branchName}.";
        }

        if ($paymentMethod) {
            $messageParts[] = 'Payment: '.str_replace('_', ' ', $paymentMethod).'.';
        }

        $this->notifications->notifyAdmins(
            type: AdminNotificationType::OrderCreated,
            title: "New Order #{$orderNumber}",
            message: implode(' ', $messageParts),
            related: $order,
            branchId: $order->branch_id,
            actionUrl: "/admin/orders/{$orderNumber}",
            dedupeKey: "order.created:{$order->id}",
            data: [
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'amount' => $order->grand_total,
                'branch_name' => $branchName,
                'order_type' => $order->type?->value,
                'payment_method' => $paymentMethod,
            ],
        );
    }
}
