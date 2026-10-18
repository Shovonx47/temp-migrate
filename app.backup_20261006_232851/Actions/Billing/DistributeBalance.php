<?php

namespace App\Actions\Billing;

use App\Enums\UserType;
use App\Models\SpDistribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

readonly class DistributeBalance
{
    public function __construct(
        private DebitBalance $debitBalance,
        private CreditBalance $creditBalance,
    ) {}

    /**
     * Move a discrete amount from an S P wallet to a customer wallet (zero-sum).
     *
     * @throws \Throwable
     */
    public function handle(
        User $sp,
        User $customer,
        float $amount,
        ?string $note = null,
        ?User $distributedBy = null,
    ): SpDistribution {
        if ($sp->type !== UserType::Sp) {
            throw new InvalidArgumentException('Source user must be an S P.');
        }

        if ($customer->type !== UserType::Customer || $customer->sp_id !== $sp->id) {
            throw new InvalidArgumentException('Target must be a customer owned by this S P.');
        }

        if ($amount === 0.0 || $amount === 0) {
            throw new InvalidArgumentException('Distribution amount must not be zero.');
        }

        return DB::transaction(function () use ($sp, $customer, $amount, $note, $distributedBy) {
            $absAmount = abs($amount);
            $isReversal = $amount < 0;

            $distribution = SpDistribution::query()->create([
                'sp_id' => $sp->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'note' => $note,
                'distributed_by' => $distributedBy?->id ?? $sp->id,
                'created_at' => now(),
            ]);

            $description = $note
                ? ($isReversal ? "S P reversal: {$note}" : "S P distribution: {$note}")
                : ($isReversal ? "S P reversal #{$distribution->id}" : "S P distribution #{$distribution->id}");

            if ($isReversal) {
                // Reversal: debit the customer, credit the SP back.
                $lockedCustomer = User::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

                if ((float) $lockedCustomer->balance < $absAmount) {
                    throw new RuntimeException('Customer has insufficient balance to reverse this amount.');
                }

                $debitTransaction = $this->debitBalance->handle(
                    $lockedCustomer,
                    $absAmount,
                    $distribution,
                    $description,
                );

                $this->creditBalance->handle(
                    $sp,
                    $absAmount,
                    $distribution,
                    $description,
                );
            } else {
                // Normal distribution: debit SP, credit customer.
                $debitTransaction = $this->debitBalance->handle(
                    $sp,
                    $absAmount,
                    $distribution,
                    $description,
                );

                $this->creditBalance->handle(
                    $customer,
                    $absAmount,
                    $distribution,
                    $description,
                );
            }

            $distribution->update([
                'transaction_id' => $debitTransaction->id,
            ]);

            return $distribution->fresh(['sp', 'customer', 'debitTransaction']);
        }, attempts: 3);
    }
}
