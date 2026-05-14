<?php

namespace App\Http\Controllers;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Services\BalanceService;
use App\Services\Payments\PaymentGatewayResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __invoke(Request $request, string $gateway)
    {
        $resolver = app(PaymentGatewayResolver::class);
        $driver = $resolver->resolve($gateway);

        $ppId = $request->query('pp_id');

        if (! $ppId) {
            return redirect()->to('/')->with('error', 'Missing payment reference.');
        }

        try {
            $data = $driver->verify($ppId);
        } catch (\Throwable $e) {
            Log::error('PipraPay callback verification failed', [
                'gateway' => $gateway,
                'pp_id' => $ppId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to('/')->with('error', 'Payment verification failed.');
        }

        $deposit = Deposit::where('transaction_id', $ppId)->first();

        if (! $deposit) {
            Log::warning('PipraPay callback for unknown deposit', ['pp_id' => $ppId]);

            return redirect()->to('/')->with('error', 'Deposit not found.');
        }

        if ($deposit->status !== DepositStatus::Pending) {
            return redirect()->to('/')->with('info', 'This deposit has already been processed.');
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
                description: 'Deposit via PipraPay',
            );

            return redirect()->to('/')->with('success', 'Payment successful! Your balance has been updated.');
        }

        if ($status === 'failed') {
            $deposit->update([
                'status' => DepositStatus::Failed,
                'meta' => $data,
            ]);

            return redirect()->to('/')->with('error', 'Payment failed. Please try again.');
        }

        return redirect()->to('/')->with('info', 'Your payment is being processed.');
    }
}
