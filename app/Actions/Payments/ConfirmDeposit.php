<?php

namespace App\Actions\Payments;

use App\Actions\Billing\CreditBalance;
use App\Enums\DepositStatus;
use App\Models\Deposit;
use Illuminate\Support\Facades\DB;

readonly class ConfirmDeposit
{
    public function __construct(
        private CreditBalance $creditBalance,
    ) {}

    /**
     * Update a deposit's status based on payment confirmation data.
     * Credits the user's balance when payment is completed.
     *
     * @throws \Throwable
     */
    public function handle(Deposit $deposit, array $data, string $via): void
    {
        $status = $data['status'] ?? null;

        if ($status === 'completed') {
            DB::transaction(function () use ($deposit, $data, $via) {
                $deposit = Deposit::query()
                    ->whereKey($deposit->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($deposit->status !== DepositStatus::Pending) {
                    return;
                }

                $deposit->update([
                    'status' => DepositStatus::Completed,
                    'meta' => $data,
                    'paid_at' => now(),
                ]);

                $this->creditBalance->handle(
                    user: $deposit->user,
                    amount: (float) $deposit->amount,
                    transactionable: $deposit,
                    description: "Deposit via {$via}",
                );
            }, attempts: 3);
        } elseif ($status === 'failed') {
            DB::transaction(function () use ($deposit, $data) {
                $deposit = Deposit::query()
                    ->whereKey($deposit->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($deposit->status !== DepositStatus::Pending) {
                    return;
                }

                $deposit->update([
                    'status' => DepositStatus::Failed,
                    'meta' => $data,
                ]);
            }, attempts: 3);
        }
    }
}
