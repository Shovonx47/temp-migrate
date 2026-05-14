<?php

namespace App\Enums;

enum TransactionableType: string
{
    case Deposit = 'deposit';
    case Campaign = 'campaign';
    case Message = 'message';
}
