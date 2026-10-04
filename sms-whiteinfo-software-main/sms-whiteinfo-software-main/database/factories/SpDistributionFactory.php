<?php

namespace Database\Factories;

use App\Models\SpDistribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpDistribution>
 */
class SpDistributionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sp_id' => User::factory()->sp(),
            'customer_id' => User::factory()->state([
                'type' => 'customer',
                'status' => 'approved',
            ]),
            'amount' => fake()->randomFloat(4, 10, 5000),
            'transaction_id' => null,
            'note' => fake()->optional()->sentence(),
            'distributed_by' => null,
            'created_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (SpDistribution $distribution): void {
            User::withoutGlobalScopes()
                ->whereKey($distribution->customer_id)
                ->update([
                    'sp_id' => $distribution->sp_id,
                    'type' => 'customer',
                ]);

            if ($distribution->distributed_by === null) {
                $distribution->updateQuietly([
                    'distributed_by' => $distribution->sp_id,
                ]);
            }
        });
    }
}
