<?php

namespace Database\Factories;

use App\Enums\SenderType;
use App\Models\Gateway;
use App\Models\Sender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sender>
 */
class SenderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gateway_id' => Gateway::factory(),
            'sender_id' => fake()->unique()->regexify('[A-Za-z0-9]{8}'),
            'type' => fake()->randomElement(SenderType::cases()),
            'is_enabled' => false,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => true,
        ]);
    }
}
