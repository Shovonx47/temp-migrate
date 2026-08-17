<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'type' => 'customer',
            'sp_id' => null,
            'status' => fake()->randomElement(['approved', 'pending', 'rejected']),

            'balance' => 10000,

            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            'email_verified_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            if (filled($user->phone)) {
                return;
            }

            $user->phone = '+8801'
                .fake()->randomElement([3, 4, 5, 6, 7, 8, 9])
                .fake()->unique()->numerify('########');
        });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function sp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sp',
            'status' => 'approved',
            'sp_id' => null,
        ]);
    }

    public function employeeOf(User $sp): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'sp',
            'sp_id' => $sp->id,
            'status' => 'approved',
            'balance' => 0,
        ]);
    }

    public function customerOf(User $sp): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
            'sp_id' => $sp->id,
            'status' => 'approved',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function superman(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'superman',
            'status' => 'approved',
            'sp_id' => null,
        ]);
    }
}
