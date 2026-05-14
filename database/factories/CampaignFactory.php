<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Sender;
use App\Models\Template;
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
            'template_id' => Template::factory(),
            'sender_id' => Sender::factory(),
            'name' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'status' => fake()->randomElement(CampaignStatus::cases()),
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+1 week'),
            'started_at' => null,
            'completed_at' => null,
            'total_recipients' => fake()->numberBetween(0, 10000),
            'sent_count' => fake()->numberBetween(0, 10000),
            'delivered_count' => fake()->numberBetween(0, 10000),
            'failed_count' => fake()->numberBetween(0, 1000),
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

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::Completed,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'sent_count' => fake()->numberBetween(1, 10000),
        ]);
    }
}
