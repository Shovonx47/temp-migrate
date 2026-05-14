<?php

namespace App\Contracts;

use App\Models\Message;

interface SmsGateway
{
    public function send(Message $message): bool;
}
