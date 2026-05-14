<?php

namespace App\Http\Controllers;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Services\BalanceService;
use App\Services\Payments\PaymentGatewayResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway)
    {
        $resolver = app(PaymentGatewayResolver::class);
        $driver = $resolver->resolve($gateway);

        try {
            $data = $driver->handleWebhook($request);
        } catch (\Throwable $e) {
            Log::error('PipraPay webhook validation failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }

        $ppId = $data['pp_id'];
        $deposit = Deposit::where('transaction_id', $ppId)->first();

        if (! $deposit) {
            Log::warning('PipraPay webhook for unknown deposit', ['pp_id' => $ppId]);

            return response()->json(['status' => 'error', 'message' => 'Deposit not found'], 404);
        }

        if ($deposit->status !== DepositStatus::Pending) {
            return response()->json(['status' => 'ok', 'message' => 'Already processed']);
        }

        $status = $data['status'] ?? null;

        if ($status === 'completed') {
            $deposit->update([
                'status' => DepositStatus::Completed,
                'meta' => $data,
                'paid_at' => now(),
            ]);

            app(BalanceService::class)->credit(
                user: $deposit->user,
                amount: (float) $data['amount'],
                transactionable: $deposit,
                description: 'Deposit via PipraPay webhook',
            );
        } elseif ($status === 'failed') {
            $deposit->update([
                'status' => DepositStatus::Failed,
                'meta' => $data,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
