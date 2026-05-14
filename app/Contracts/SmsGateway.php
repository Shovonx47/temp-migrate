<?php

namespace App\Contracts;

use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface SmsGateway
{
    public function send(Message $message): bool;
}
