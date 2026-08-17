<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentRequest;
use App\Services\Payment\Data\PaymentResponse;
use Illuminate\Support\Facades\Log;

/**
 * No-op payment driver used for testing / local development.
 *
 * Simulates a successful checkout (returns a fake checkout URL and
 * payment id) so deposit flows can be exercised end-to-end without
 * calling an external payment gateway.
 */
final class LogDriver implements PaymentGateway
{
    public function __construct(private readonly array $config = []) {}

    public function create(PaymentRequest $request): PaymentResponse
    {
        Log::channel('stack')->info('Payment [log] checkout created', [
            'order_id' => $request->orderId,
            'amount' => $request->amount,
            'currency' => $request->currency,
        ]);

        return new PaymentResponse(
            successful: true,
            gatewayDriver: 'log',
            gatewayStatus: 'pending',
            gatewayPaymentId: 'log-pp-'.uniqid(),
            checkoutUrl: '#simulated-checkout',
            amount: $request->amount,
            currency: $request->currency,
            raw: [
                'order_id' => $request->orderId,
                'amount' => $request->amount,
                'currency' => $request->currency,
            ],
        );
    }

    public function verify(array $payload): PaymentResponse
    {
        Log::channel('stack')->info('Payment [log] verify called', $payload);

        return new PaymentResponse(
            successful: true,
            gatewayDriver: 'log',
            gatewayStatus: 'completed',
            gatewayPaymentId: (string) ($payload['pp_id'] ?? 'log-pp-'.uniqid()),
            raw: $payload,
        );
    }

    public function handleWebhook(array $payload): PaymentResponse
    {
        Log::channel('stack')->info('Payment [log] webhook received', $payload);

        return new PaymentResponse(
            successful: true,
            gatewayDriver: 'log',
            gatewayStatus: 'completed',
            gatewayPaymentId: (string) ($payload['pp_id'] ?? 'log-pp-'.uniqid()),
            amount: (int) ($payload['amount'] ?? 0),
            currency: (string) ($payload['currency'] ?? 'BDT'),
            raw: $payload,
        );
    }
}
