<?php

namespace App\Listeners;

use App\Enums\AdminNotificationType;
use App\Events\OrderModifiedByCustomer;
use App\Support\AdminNotificationService;

class CreateOrderModifiedAdminNotification
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function handle(OrderModifiedByCustomer $event): void
    {
        $order = $event->order->loadMissing(['branch', 'user']);
        $orderNumber = $order->public_order_number;
        $customerName = $order->user?->name ?? 'A customer';
        $branchName = $order->branch?->name;
        $amount = $this->notifications->formatMoney($order->grand_total);

        $message = "{$customerName} modified Order #{$orderNumber}. Current total: {$amount}.";

        if ($branchName) {
            $message .= " Branch: {$branchName}.";
        }

        $this->notifications->notifyAdmins(
            type: AdminNotificationType::OrderModifiedByCustomer,
            title: "Order Modified #{$orderNumber}",
            message: $message,
            related: $order,
            branchId: $order->branch_id,
            actionUrl: "/admin/orders/{$orderNumber}",
            dedupeKey: "order.modified_by_customer:{$order->id}:".$order->updated_at?->timestamp,
            data: [
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'branch_name' => $branchName,
                'amount' => $order->grand_total,
            ],
        );
    }
}
