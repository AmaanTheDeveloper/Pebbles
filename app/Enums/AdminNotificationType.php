<?php

namespace App\Enums;

enum AdminNotificationType: string
{
    case OrderCreated = 'order.created';
    case OrderCancelledByCustomer = 'order.cancelled_by_customer';
    case OrderModifiedByCustomer = 'order.modified_by_customer';
    case PaymentProofUploaded = 'payment.proof_uploaded';
    case ReviewSubmitted = 'review.submitted';
    case ContactMessageSubmitted = 'contact.message_submitted';
    case CustomerRegistered = 'customer.registered';

    public function category(): AdminNotificationCategory
    {
        return match ($this) {
            self::OrderCreated,
            self::OrderCancelledByCustomer,
            self::OrderModifiedByCustomer => AdminNotificationCategory::Orders,
            self::PaymentProofUploaded => AdminNotificationCategory::Payments,
            self::ReviewSubmitted => AdminNotificationCategory::Reviews,
            self::ContactMessageSubmitted => AdminNotificationCategory::Contact,
            self::CustomerRegistered => AdminNotificationCategory::Customers,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OrderCreated,
            self::OrderCancelledByCustomer,
            self::OrderModifiedByCustomer => 'order',
            self::PaymentProofUploaded => 'payment',
            self::ReviewSubmitted => 'review',
            self::ContactMessageSubmitted => 'contact',
            self::CustomerRegistered => 'customer',
        };
    }

    public function defaultPriority(): AdminNotificationPriority
    {
        return match ($this) {
            self::PaymentProofUploaded => AdminNotificationPriority::Important,
            self::OrderCancelledByCustomer => AdminNotificationPriority::Important,
            default => AdminNotificationPriority::Normal,
        };
    }
}
