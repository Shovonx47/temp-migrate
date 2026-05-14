<?php

namespace App\Actions\Payments;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Support\Balance;
use Illuminate\Support\Facades\DB;

class ConfirmDeposit
{
    /**
     * Update a deposit's status based on payment confirmation data.
     * Credits the user's balance when payment is completed.
     * @throws \Throwable
     */
    public function handle(Deposit $deposit, array $data, string $via): void
    {
        if ($deposit->status !== DepositStatus::Pending) {
            return;
        }

        $status = $data['status'] ?? null;

        if ($status === 'completed') {
            DB::transaction(function () use ($deposit, $data, $via) {
                $deposit->update([
                    'status' => DepositStatus::Completed,
                    'meta' => $data,
                    'paid_at' => now(),
                ]);

                app(Balance::class)->credit(
                    user: $deposit->user,
                    amount: (float) $data['amount'],
                    transactionable: $deposit,
                    description: "Deposit via {$via}",
                );
            });
        } elseif ($status === 'failed') {
            $deposit->update([
                'status' => DepositStatus::Failed,
                'meta' => $data,
            ]);
        }
    }
}
