<?php

namespace App\Actions\Billing;

use App\Enums\UserType;
use App\Models\SpDistribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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

        if ($amount <= 0) {
            throw new InvalidArgumentException('Distribution amount must be greater than zero.');
        }

        return DB::transaction(function () use ($sp, $customer, $amount, $note, $distributedBy) {
            $distribution = SpDistribution::query()->create([
                'sp_id' => $sp->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'note' => $note,
                'distributed_by' => $distributedBy?->id ?? $sp->id,
                'created_at' => now(),
            ]);

            $description = $note
                ? "S P distribution: {$note}"
                : "S P distribution #{$distribution->id}";

            $debitTransaction = $this->debitBalance->handle(
                $sp,
                $amount,
                $distribution,
                $description,
            );

            $this->creditBalance->handle(
                $customer,
                $amount,
                $distribution,
                $description,
            );

            $distribution->update([
                'transaction_id' => $debitTransaction->id,
            ]);

            return $distribution->fresh(['sp', 'customer', 'debitTransaction']);
        }, attempts: 3);
    }
}
