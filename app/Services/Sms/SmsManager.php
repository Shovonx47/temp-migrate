<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Services\Sms\Drivers\LogDriver;
use App\Services\Sms\Drivers\RevesmsDriver;
use Illuminate\Support\Manager;

class SmsManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('sms.default');
    }

    public function createLogDriver(): SmsGateway
    {
        return new LogDriver($this->config->get('sms.drivers.log', []));
    }

    public function createRevesmsDriver(): SmsGateway
    {
        return new RevesmsDriver($this->config->get('sms.drivers.revesms', []));
    }
}
