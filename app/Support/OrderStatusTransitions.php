<?php

namespace App\Support;

use App\Enums\OrderStatus;

class OrderStatusTransitions
{
    /**
     * @return list<OrderStatus>
     */
    public function allowedNext(OrderStatus $current): array
    {
        return match ($current) {
            OrderStatus::Pending => [OrderStatus::Confirmed, OrderStatus::Cancelled],
            OrderStatus::Confirmed => [OrderStatus::Preparing, OrderStatus::Cancelled],
            OrderStatus::Preparing => [OrderStatus::Ready, OrderStatus::Cancelled],
            OrderStatus::Ready => [OrderStatus::OutForDelivery, OrderStatus::Completed, OrderStatus::Cancelled],
            OrderStatus::OutForDelivery => [OrderStatus::Completed, OrderStatus::Cancelled],
            OrderStatus::Completed, OrderStatus::Cancelled => [],
        };
    }

    public function canTransition(OrderStatus $from, OrderStatus $to): bool
    {
        if ($from === $to) {
            return false;
        }

        return in_array($to, $this->allowedNext($from), true);
    }
}
