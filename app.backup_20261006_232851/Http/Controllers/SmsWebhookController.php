<?php

namespace App\Http\Controllers;

class SmsWebhookController extends Controller
{
    public function __invoke()
    {
        return response()->json(['message' => 'SMS webhook received.']);
    }
}
