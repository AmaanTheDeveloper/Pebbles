<?php

namespace App\Enums;

enum OrderType: string
{
    case Delivery = 'delivery';
    case Takeaway = 'takeaway';
    case DineIn = 'dine_in';
}
