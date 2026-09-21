<?php

namespace App\Enums;

enum DippingRateMode: string
{
    case PerSms = 'per_sms';
    case Percentage = 'percentage';
}
