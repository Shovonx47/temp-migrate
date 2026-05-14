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
            'meta' => null,
            'is_blocked' => false,
        ];
    }

    public function configure(): static
    {
        return $this->sequence(fn ($sequence) => [
            'phone' => '+8801'
                .fake()->randomElement([3, 4, 5, 6, 7, 8, 9])
                .str_pad($sequence->index, 8, '0', STR_PAD_LEFT),
        ]);
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
