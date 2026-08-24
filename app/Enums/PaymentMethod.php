<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case EasyPaisa = 'easypaisa';
    case JazzCash = 'jazzcash';
    case BankTransfer = 'bank_transfer';
}
