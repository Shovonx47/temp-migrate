<?php

namespace Database\Factories;

use App\Enums\GatewayDriver;
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
            'driver' => fake()->randomElement(GatewayDriver::cases()),
            'credentials' => null,
            'config' => null,
            'requests_per_second' => 10,
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
