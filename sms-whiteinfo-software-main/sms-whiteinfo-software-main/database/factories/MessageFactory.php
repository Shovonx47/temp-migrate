<?php

namespace Database\Factories;

use App\Enums\MessageStatus;
use App\Models\Campaign;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'sender_id' => Sender::factory(),
            'to' => fake()->numerify('+88017XXXXXXXX'),
            'body' => fake()->sentence(),
            'status' => fake()->randomElement(MessageStatus::cases()),
            'gateway_message_id' => fake()->optional()->uuid(),
            'cost' => fake()->randomFloat(4, 0, 5),
            'is_unicode' => false,
            'failed_reason' => null,
            'sent_at' => null,
            'failed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::Pending,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::Failed,
            'failed_reason' => fake()->sentence(),
            'failed_at' => now(),
        ]);
    }

    public function unicode(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_unicode' => true,
            'body' => 'আপনার কোড: 123456',
        ]);
    }
}
