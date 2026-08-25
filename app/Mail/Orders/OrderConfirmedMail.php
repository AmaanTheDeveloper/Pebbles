<?php

namespace App\Mail\Orders;

use App\Enums\CustomerEmailType;
use App\Models\Order;
use App\Support\Orders\OrderConfirmedMailData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

class OrderConfirmedMail extends Mailable implements ShouldBeUnique, ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 180];

    public int $uniqueFor = 3600;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return CustomerEmailType::OrderConfirmed->value.':'.$this->order->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pebbles Order Confirmed — #'.$this->order->public_order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.orders.confirmed',
            text: 'emails.orders.confirmed-text',
            with: OrderConfirmedMailData::for($this->order),
        );
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('Order confirmation email failed.', [
            'email_type' => CustomerEmailType::OrderConfirmed->value,
            'order_id' => $this->order->id,
            'order_number' => $this->order->public_order_number,
            'recipient' => $this->order->user?->email,
            'error' => $exception?->getMessage(),
        ]);
    }
}
