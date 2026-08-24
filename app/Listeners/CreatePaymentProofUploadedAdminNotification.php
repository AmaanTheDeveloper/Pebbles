<?php

namespace App\Listeners;

use App\Enums\AdminNotificationType;
use App\Events\PaymentProofUploaded;
use App\Support\AdminNotificationService;

class CreatePaymentProofUploadedAdminNotification
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function handle(PaymentProofUploaded $event): void
    {
        $order = $event->order->loadMissing(['branch', 'user']);
        $payment = $event->payment;
        $orderNumber = $order->public_order_number;
        $customerName = $order->user?->name ?? 'A customer';
        $branchName = $order->branch?->name;
        $method = str_replace('_', ' ', (string) $payment->method?->value);
        $amount = $this->notifications->formatMoney($payment->amount);

        $message = "{$customerName} uploaded {$method} payment proof for Order #{$orderNumber} ({$amount}).";

        if ($branchName) {
            $message .= " Branch: {$branchName}.";
        }

        $this->notifications->notifyAdmins(
            type: AdminNotificationType::PaymentProofUploaded,
            title: "Payment Proof Uploaded #{$orderNumber}",
            message: $message,
            related: $payment,
            branchId: $order->branch_id,
            actionUrl: "/admin/payments/{$payment->id}",
            dedupeKey: "payment.proof_uploaded:{$event->proof->id}",
            data: [
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'branch_name' => $branchName,
                'payment_method' => $payment->method?->value,
                'amount' => $payment->amount,
                'payment_id' => $payment->id,
            ],
        );
    }
}
