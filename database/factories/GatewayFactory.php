<?php

namespace Database\Factories;

use App\Models\Gateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gateway>
 */
class GatewayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->word()).' SMS',
            'driver' => fake()->slug(1),
            'credentials' => null,
            'config' => null,
            'daily_limit' => fake()->optional()->numberBetween(1000, 100000),
            'monthly_limit' => fake()->optional()->numberBetween(10000, 1000000),
            'is_enabled' => false,
            'supports_bulk' => false,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => true,
        ]);
    }

    public function withLimits(): static
    {
        return $this->state(fn (array $attributes) => [
            'daily_limit' => 5000,
            'monthly_limit' => 150000,
        ]);
    }

    public function withCredentials(): static
    {
        return $this->state(fn (array $attributes) => [
            'credentials' => [
                'api_key' => fake()->uuid(),
                'api_secret' => fake()->sha256(),
            ],
        ]);
    }
}
