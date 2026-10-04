<?php

namespace App\Contracts;

use App\Services\Sms\Data\BulkSmsRequest;
use App\Services\Sms\Data\BulkSmsResponse;

interface SupportsBulkSms
{
    public function sendBulk(BulkSmsRequest $request): BulkSmsResponse;
}
