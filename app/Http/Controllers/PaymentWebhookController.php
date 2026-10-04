<?php

namespace App\Http\Controllers;

use App\Actions\Billing\CompleteDeposit;
use App\Models\PaymentWebhook;
use App\Support\Facades\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentWebhookController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(
        Request $request,
        string $gateway,
        CompleteDeposit $completeDeposit,
    ) {
        try {
            $response = Payment::driver($gateway)->handleWebhook($request->all());
        } catch (Throwable $e) {
            Log::warning('Webhook verification failed', [
                'gateway' => $gateway,
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid webhook.']);
        }

        $webhook = PaymentWebhook::firstOrCreate(
            [
                'gateway' => $gateway,
                'event_id' => $response->gatewayPaymentId,
            ],
            [
                'event_type' => 'payment.updated',
                'status' => $response->gatewayStatus,
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
            ]
        );

        if (! $webhook->wasRecentlyCreated) {
            return response()->json(['message' => 'Webhook already processed.']);
        }

        $completeDeposit->handle($gateway, $response);

        return response()->json(['message' => 'Webhook received.']);
    }
}
