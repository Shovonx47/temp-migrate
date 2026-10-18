<?php

namespace App\Actions\Billing;

use App\Contracts\Transactionable;
use App\Enums\TransactionDirection;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

readonly class DebitBalance
{
    /**
     * @throws \Throwable
     */
    public function handle(
        User $user,
        float $amount,
        Transactionable $transactionable,
        ?string $description = null,
    ): Transaction {
        if ($amount <= 0) {
            throw new RuntimeException('Debit amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $amount, $transactionable, $description) {
            $user = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $user->balance;

            if ($balanceBefore < $amount) {
                throw new RuntimeException('Insufficient balance.');
            }

            $balanceAfter = $balanceBefore - $amount;

            $user->forceFill(['balance' => $balanceAfter])->save();

            return $transactionable->transactions()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'direction' => TransactionDirection::Debit,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description ?? 'Balance debited',
            ]);
        }, attempts: 3);
    }
}
