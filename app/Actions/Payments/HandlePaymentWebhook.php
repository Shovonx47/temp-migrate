<?php

namespace App\Actions\Payments;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HandlePaymentWebhook
{
    public function __construct(
        private PaymentManager $manager,
        private ConfirmDeposit $confirmDeposit,
    ) {}

    /**
     * Handle an incoming webhook from the payment gateway.
     */
    public function handle(string $gateway, Request $request): JsonResponse
    {
        $driver = $this->manager->driver($gateway);

        try {
            $data = $driver->handleWebhook($request);
        } catch (\Throwable $e) {
            Log::error('Payment webhook validation failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        $ppId = $data['pp_id'];
        $deposit = Deposit::where('transaction_id', $ppId)->first();

        if (! $deposit) {
            Log::warning('Payment webhook for unknown deposit', ['pp_id' => $ppId]);

            return response()->json(['status' => 'error', 'message' => 'Deposit not found'], 404);
        }

        if ($deposit->status !== DepositStatus::Pending) {
            return response()->json(['status' => 'ok', 'message' => 'Already processed']);
        }

        $this->confirmDeposit->handle($deposit, $data, 'PipraPay webhook');

        return response()->json(['status' => 'ok']);
    }
}
