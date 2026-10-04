<?php

namespace App\Enums;

enum DeliveryOutcome: string
{
    case Delivered = 'delivered';
    case Failed = 'failed';

    /**
     * The message has been accepted by the SMSC/carrier but not yet delivered.
     * The driver should be polled again later.
     */
    case InTransit = 'in_transit';
}
