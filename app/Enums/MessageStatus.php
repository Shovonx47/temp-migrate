<?php

namespace App\Enums;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
