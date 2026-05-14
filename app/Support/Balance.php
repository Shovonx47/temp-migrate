<?php

namespace App\Support;

use App\Contracts\Transactionable;
use App\Enums\TransactionDirection;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class Balance
{
    /**
     * Credit a user's balance. Creates an immutable transaction record.
     * @throws Throwable
     */
    public function credit(User $user, float $amount, Transactionable $transactionable, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $transactionable, $description) {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float)$user->balance;
            $balanceAfter = $balanceBefore + $amount;

            $user->balance = $balanceAfter;
            $user->save();

            return $this->record(
                user: $user,
                amount: $amount,
                direction: TransactionDirection::Credit,
                balanceBefore: $balanceBefore,
                balanceAfter: $balanceAfter,
                transactionable: $transactionable,
                description: $description ?? 'Balance credited',
            );
        });
    }

    /**
     * Debit a user's balance. Throws if insufficient balance.
     * @throws Throwable
     */
    public function debit(User $user, float $amount, Transactionable $transactionable, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $transactionable, $description) {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float)$user->balance;

            if ($balanceBefore < $amount) {
                throw new RuntimeException('Insufficient balance.');
            }

            $balanceAfter = $balanceBefore - $amount;

            $user->balance = $balanceAfter;
            $user->save();

            return $this->record(
                user: $user,
                amount: $amount,
                direction: TransactionDirection::Debit,
                balanceBefore: $balanceBefore,
                balanceAfter: $balanceAfter,
                transactionable: $transactionable,
                description: $description ?? 'Balance debited',
            );
        });
    }

    /**
     * Check if a user has sufficient balance.
     */
    public function hasBalance(User $user, float $amount): bool
    {
        return (float)$user->balance >= $amount;
    }

    private function record(
        User                 $user,
        float                $amount,
        TransactionDirection $direction,
        float                $balanceBefore,
        float                $balanceAfter,
        Transactionable      $transactionable,
        string               $description,
    ): Transaction
    {
        return $transactionable->transactions()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'direction' => $direction,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);
    }
}
