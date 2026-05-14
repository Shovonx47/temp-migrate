<?php

namespace App\Actions\Payments;

use App\Actions\Billing\DebitBalance;
use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

readonly class RefundDeposit
{
    public function __construct(
        private PaymentManager $manager,
        private DebitBalance $debitBalance,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Deposit $deposit): void
    {
        if ($deposit->status !== DepositStatus::Completed) {
            throw new RuntimeException('Only completed deposits can be refunded.');
        }

        try {
            $driver = $this->manager->driver($deposit->payment_method);
        } catch (InvalidArgumentException) {
            $methodName = ucfirst($deposit->payment_method);
            throw new RuntimeException("This deposit cannot be refunded through {$methodName}.");
        }

        $driver->refund($deposit->transaction_id);

        DB::transaction(function () use ($deposit) {
            $deposit = Deposit::query()
                ->whereKey($deposit->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($deposit->status !== DepositStatus::Completed) {
                return;
            }

            $deposit->update(['status' => DepositStatus::Refunded]);

            $methodName = ucfirst($deposit->payment_method);

            $this->debitBalance->handle(
                user: $deposit->user,
                amount: (float) $deposit->amount,
                transactionable: $deposit,
                description: "Refund via {$methodName}",
            );
        }, attempts: 3);
    }
}
