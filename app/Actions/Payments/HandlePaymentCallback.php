<?php

namespace App\Actions\Payments;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

readonly class HandlePaymentCallback
{
    public function __construct(
        private PaymentManager $manager,
        private ConfirmDeposit $confirmDeposit,
    ) {}

    /**
     * Handle a browser redirect callback from the payment gateway.
     */
    public function handle(string $gateway, string $ppId): RedirectResponse
    {
        $driver = $this->manager->driver($gateway);

        try {
            $data = $driver->verify($ppId);
        } catch (\Throwable $e) {
            Log::error('Payment callback verification failed', [
                'gateway' => $gateway,
                'pp_id' => $ppId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->to('/')->with('error', 'Payment verification failed.');
        }

        $deposit = Deposit::where('transaction_id', $ppId)->first();

        if (! $deposit) {
            Log::warning('Payment callback for unknown deposit', ['pp_id' => $ppId]);

            return redirect()->to('/')->with('error', 'Deposit not found.');
        }

        if ($deposit->status !== DepositStatus::Pending) {
            return redirect()->to('/')->with('info', 'This deposit has already been processed.');
        }

        $this->confirmDeposit->handle($deposit, $data, 'PipraPay');

        $status = $data['status'] ?? null;

        return match ($status) {
            'completed' => redirect()->to('/')->with('success', 'Payment successful! Your balance has been updated.'),
            'failed' => redirect()->to('/')->with('error', 'Payment failed. Please try again.'),
            default => redirect()->to('/')->with('info', 'Your payment is being processed.'),
        };
    }
}
