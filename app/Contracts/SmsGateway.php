<?php

namespace App\Contracts;

use App\Services\Sms\Data\SmsRequest;
use App\Services\Sms\Data\SmsResponse;

interface SmsGateway
{
    public function send(SmsRequest $request): SmsResponse;
}
