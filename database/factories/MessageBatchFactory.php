<?php

namespace Database\Factories;

use App\Enums\MessageBatchStatus;
use App\Models\MessageBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageBatch>
 */
class MessageBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
            'source_type' => 'manual',
            'source_config' => ['recipients' => []],
            'status' => MessageBatchStatus::Processing,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => MessageBatchStatus::Completed,
            'total_messages' => fake()->numberBetween(1, 100),
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => MessageBatchStatus::Failed,
            'started_at' => now()->subMinutes(1),
            'completed_at' => now(),
        ]);
    }

    public function upload(): static
    {
        return $this->state(fn (): array => [
            'source_type' => 'upload',
            'source_config' => [
                'disk' => 'local',
                'file_path' => 'uploads/test.csv',
                'mapping' => ['phone' => 'phone', 'variables' => []],
            ],
        ]);
    }

    public function group(): static
    {
        return $this->state(fn (): array => [
            'source_type' => 'group',
            'source_config' => ['group_ids' => []],
        ]);
    }
}
