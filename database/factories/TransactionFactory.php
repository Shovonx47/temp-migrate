<?php

namespace Database\Factories;

use App\Enums\TransactionDirection;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 1, 1000);
        $balanceBefore = fake()->randomFloat(2, 0, 10000);

        return [
            'user_id' => User::factory(),
            'transactionable_type' => Deposit::class,
            'transactionable_id' => Deposit::factory(),
            'type' => 'deposit',
            'amount' => $amount,
            'direction' => fake()->randomElement(TransactionDirection::cases()),
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => TransactionDirection::Credit,
            'balance_after' => $attributes['balance_before'] + $attributes['amount'],
        ]);
    }

    public function debit(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => TransactionDirection::Debit,
            'balance_after' => $attributes['balance_before'] - $attributes['amount'],
        ]);
    }
}
