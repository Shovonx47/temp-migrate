<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Deposit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LogDriver implements PaymentGateway
{
    public function __construct(private array $config) {}

    public function initiate(Deposit $deposit): RedirectResponse
    {
        logger()->info('Payment initiated (log driver)', [
            'deposit_id' => $deposit->id,
            'amount' => $deposit->amount,
        ]);

        return redirect()->to('/');
    }

    public function verify(string $ppId): array
    {
        logger()->info('Payment verified (log driver)', ['pp_id' => $ppId]);

        return [
            'pp_id' => $ppId,
            'status' => 'completed',
            'amount' => 0,
        ];
    }

    public function handleWebhook(Request $request): array
    {
        return $request->validate([
            'pp_id' => ['required', 'string'],
            'status' => ['required', 'string'],
            'amount' => ['required'],
        ]);
    }

    public function refund(string $ppId): array
    {
        logger()->info('Payment refunded (log driver)', ['pp_id' => $ppId]);

        return ['status' => 'refunded'];
    }
}
