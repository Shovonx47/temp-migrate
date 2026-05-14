<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->optional()->name(),
            'phone' => fake()->unique()->numerify('+88017XXXXXXXX'),
            'meta' => null,
            'is_blocked' => false,
        ];
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
        ]);
    }

    public function withMeta(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta' => [
                'email' => fake()->safeEmail(),
                'dob' => fake()->date(),
            ],
        ]);
    }
}
