<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;

class CustomerOrderActions
{
    public const CHANGES_CLOSED_HEADING = 'Cancel or Modify Order window is closed.';

    public const CHANGES_CLOSED_MESSAGE = 'Your order has been confirmed and is now being prepared. Changes or cancellation are no longer available.';

    public const CHANGES_CLOSED_CONFIRMED_REASON = 'Cancel or Modify Order window is closed because the order has already been confirmed.';

    /**
     * @return list<OrderStatus>
     */
    public static function editableStatuses(): array
    {
        return [
            OrderStatus::Pending,
        ];
    }

    public static function isPending(Order $order): bool
    {
        return $order->status === OrderStatus::Pending;
    }

    public static function hasEditableStatus(Order $order): bool
    {
        return self::isPending($order);
    }

    public static function changesAreClosed(Order $order): bool
    {
        if (CustomerOrderLifecycle::isFinal($order)) {
            return false;
        }

        if ($order->status === OrderStatus::Cancelled || $order->cancelled_at !== null) {
            return false;
        }

        return ! self::isPending($order);
    }

    public static function canCancel(Order $order): bool
    {
        if (CustomerOrderLifecycle::isFinal($order)) {
            return false;
        }

        if (! OrderChangeSettings::cancellationEnabled()) {
            return false;
        }

        if ($order->status === OrderStatus::Cancelled || $order->cancelled_at !== null) {
            return false;
        }

        return self::isPending($order);
    }

    public static function canModify(Order $order): bool
    {
        if (CustomerOrderLifecycle::isFinal($order)) {
            return false;
        }

        if (! OrderChangeSettings::modificationEnabled()) {
            return false;
        }

        if ($order->status === OrderStatus::Cancelled || $order->cancelled_at !== null) {
            return false;
        }

        if ($order->status === OrderStatus::Completed) {
            return false;
        }

        return self::isPending($order);
    }

    public static function cancelUnavailableReason(Order $order): ?string
    {
        if (self::canCancel($order)) {
            return null;
        }

        if (CustomerOrderLifecycle::isFinal($order)) {
            return CustomerOrderLifecycle::customerStatusLabel($order);
        }

        if ($order->status === OrderStatus::Cancelled || $order->cancelled_at !== null) {
            return 'Order cancelled';
        }

        if ($order->status === OrderStatus::Completed) {
            return null;
        }

        if (self::changesAreClosed($order)) {
            return self::CHANGES_CLOSED_CONFIRMED_REASON;
        }

        if (! OrderChangeSettings::cancellationEnabled()) {
            return 'Order cancellation is currently unavailable.';
        }

        return null;
    }

    public static function modifyUnavailableReason(Order $order): ?string
    {
        if (self::canModify($order)) {
            return null;
        }

        if (CustomerOrderLifecycle::isFinal($order)) {
            return CustomerOrderLifecycle::customerStatusLabel($order);
        }

        if ($order->status === OrderStatus::Cancelled || $order->cancelled_at !== null) {
            return 'Order cancelled';
        }

        if ($order->status === OrderStatus::Completed) {
            return null;
        }

        if (self::changesAreClosed($order)) {
            return self::CHANGES_CLOSED_CONFIRMED_REASON;
        }

        if (! OrderChangeSettings::modificationEnabled()) {
            return 'Order modification is currently unavailable.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function payloadFor(Order $order): array
    {
        $canCancel = self::canCancel($order);
        $canModify = self::canModify($order);
        $changesClosed = self::changesAreClosed($order);
        $cancelReason = self::cancelUnavailableReason($order);
        $modifyReason = self::modifyUnavailableReason($order);

        return [
            'can_cancel' => $canCancel,
            'can_modify' => $canModify,
            'changes_closed' => $changesClosed,
            'changes_closed_heading' => $changesClosed ? self::CHANGES_CLOSED_HEADING : null,
            'changes_closed_message' => $changesClosed ? self::CHANGES_CLOSED_MESSAGE : null,
            'cancel_unavailable_reason' => $cancelReason,
            'modify_unavailable_reason' => $modifyReason,
            'changes_unavailable_reason' => $canCancel || $canModify
                ? null
                : ($changesClosed
                    ? self::CHANGES_CLOSED_MESSAGE
                    : ($cancelReason ?? $modifyReason)),
        ];
    }
}
