<?php

namespace App\Services\Sms\Drivers;

use App\Contracts\SmsGateway;

class RevesmsDriver implements SmsGateway
{
    public function __construct(array $config)
    {
    }
}
