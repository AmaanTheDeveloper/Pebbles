<?php

namespace App\Enums;

enum AdminNotificationPriority: string
{
    case Normal = 'normal';
    case Important = 'important';
    case Urgent = 'urgent';
}
