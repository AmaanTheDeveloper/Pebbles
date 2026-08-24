<?php

namespace App\Listeners;

use App\Enums\CustomerEmailType;
use App\Events\OrderConfirmed;
use App\Mail\Orders\OrderConfirmedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendOrderConfirmedEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30, 90];

    public function handle(OrderConfirmed $event): void
    {
        $order = $event->order->loadMissing(['user']);
        $recipient = $order->user?->email;

        if (! is_string($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            logger()->warning('Skipped order confirmation email: missing or invalid recipient.', [
                'email_type' => CustomerEmailType::OrderConfirmed->value,
                'order_id' => $order->id,
                'order_number' => $order->public_order_number,
                'recipient' => $recipient,
            ]);

            return;
        }

        Mail::to($recipient)->send(new OrderConfirmedMail($order));

        logger()->info('Order confirmation email queued.', [
            'email_type' => CustomerEmailType::OrderConfirmed->value,
            'order_id' => $order->id,
            'order_number' => $order->public_order_number,
            'recipient' => $recipient,
        ]);
    }

    public function failed(OrderConfirmed $event, Throwable $exception): void
    {
        logger()->error('Order confirmation email listener failed.', [
            'email_type' => CustomerEmailType::OrderConfirmed->value,
            'order_id' => $event->order->id,
            'order_number' => $event->order->public_order_number,
            'recipient' => $event->order->user?->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
