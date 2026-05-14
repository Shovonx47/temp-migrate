<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'variables' => null,
        ];
    }

    public function withVariables(): static
    {
        return $this->state(fn (array $attributes) => [
            'variables' => ['name', 'otp', 'amount'],
        ]);
    }
}
