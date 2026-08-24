<?php

namespace App\Listeners;

use App\Enums\AdminNotificationType;
use App\Events\OrderCancelledByCustomer;
use App\Support\AdminNotificationService;

class CreateOrderCancelledAdminNotification
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function handle(OrderCancelledByCustomer $event): void
    {
        $order = $event->order->loadMissing(['branch', 'user']);
        $orderNumber = $order->public_order_number;
        $customerName = $order->user?->name ?? 'A customer';
        $branchName = $order->branch?->name;
        $reason = trim((string) ($order->cancellation_reason ?? ''));

        $message = "{$customerName} cancelled Order #{$orderNumber}.";

        if ($branchName) {
            $message .= " Branch: {$branchName}.";
        }

        if ($reason !== '') {
            $message .= " Reason: {$reason}.";
        }

        $this->notifications->notifyAdmins(
            type: AdminNotificationType::OrderCancelledByCustomer,
            title: "Order Cancelled by Customer #{$orderNumber}",
            message: $message,
            related: $order,
            branchId: $order->branch_id,
            actionUrl: "/admin/orders/{$orderNumber}",
            dedupeKey: "order.cancelled_by_customer:{$order->id}:".($order->cancelled_at?->timestamp ?? now()->timestamp),
            data: [
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'branch_name' => $branchName,
                'cancellation_reason' => $reason !== '' ? $reason : null,
            ],
        );
    }
}
