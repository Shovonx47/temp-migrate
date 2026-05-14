<?php

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'currency' => 'BDT',
            'payment_method' => fake()->randomElement(['sslcommerz', 'stripe', 'manual']),
            'transaction_id' => fake()->unique()->uuid(),
            'status' => fake()->randomElement(DepositStatus::cases()),
            'meta' => null,
            'paid_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::Completed,
            'paid_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::Pending,
            'paid_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::Failed,
        ]);
    }
}
