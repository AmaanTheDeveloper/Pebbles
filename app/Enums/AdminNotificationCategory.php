<?php

namespace App\Enums;

enum AdminNotificationCategory: string
{
    case Orders = 'orders';
    case Payments = 'payments';
    case Reviews = 'reviews';
    case Contact = 'contact';
    case Customers = 'customers';
    case Branches = 'branches';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Orders => 'Orders',
            self::Payments => 'Payments',
            self::Reviews => 'Reviews',
            self::Contact => 'Contact',
            self::Customers => 'Customers',
            self::Branches => 'Branches',
            self::System => 'System',
        };
    }
}
