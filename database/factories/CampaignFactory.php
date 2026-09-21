<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'group_id' => Group::factory(),
            'sender_id' => Sender::factory(),
            'name' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'status' => CampaignStatus::Draft,
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'total_recipients' => 0,
            'total_sent' => 0,
            'total_failed' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Draft,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Scheduled,
            'scheduled_at' => now()->addHour(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Processing,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Completed,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Cancelled,
            'completed_at' => now(),
        ]);
    }
}
