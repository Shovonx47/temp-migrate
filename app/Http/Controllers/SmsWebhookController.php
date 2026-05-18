<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SmsWebhookController extends Controller
{
    public function __invoke()
    {
        return response()->json(['message' => 'SMS webhook received.']);
    }
}
