<?php

namespace App\Services;

use App\Enums\TransactionableType;
use App\Enums\TransactionDirection;
use App\Models\Campaign;
use App\Models\Deposit;
use App\Models\Message;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BalanceService
{
    /**
     * Credit a user's balance. Creates an immutable transaction record.
     */
    public function credit(User $user, float $amount, Model $transactionable, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $transactionable, $description) {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float) $user->balance;
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
     */
    public function debit(User $user, float $amount, Model $transactionable, ?string $description = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $transactionable, $description) {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float) $user->balance;

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
        return (float) $user->balance >= $amount;
    }

    private function record(
        User $user,
        float $amount,
        TransactionDirection $direction,
        float $balanceBefore,
        float $balanceAfter,
        Model $transactionable,
        string $description,
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'transactionable_type' => get_class($transactionable),
            'transactionable_id' => $transactionable->getKey(),
            'type' => $this->resolveType($transactionable)->value,
            'amount' => $amount,
            'direction' => $direction,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);
    }

    private function resolveType(Model $transactionable): TransactionableType
    {
        return match (get_class($transactionable)) {
            Deposit::class => TransactionableType::Deposit,
            Campaign::class => TransactionableType::Campaign,
            Message::class => TransactionableType::Message,
            default => throw new RuntimeException('Unknown transactionable type: '.get_class($transactionable)),
        };
    }
}
