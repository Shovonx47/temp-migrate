<?php

namespace App\Http\Controllers;

use App\Actions\Payments\HandlePaymentWebhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway)
    {
        return app(HandlePaymentWebhook::class)->handle($gateway, $request);
    }
}
