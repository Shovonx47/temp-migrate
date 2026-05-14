<?php

namespace App\Services\Sms\Drivers;

use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Services\Sms\SmsResult;

class LogDriver extends AbstractDriver
{
    public function __construct(
        protected array $config = [],
    )
    {
    }

    protected function sendMessage(Message $message, Sender $sender, Gateway $gateway): SmsResult
    {
        return new SmsResult(
            success: true,
            responseId: 'log-' . $message->id,
        );
    }
}
