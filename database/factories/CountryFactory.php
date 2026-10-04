<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->country(),
            'code' => (string) fake()->unique()->numberBetween(1, 999),
            'iso_alpha2' => strtoupper(fake()->lexify('??')),
            'is_active' => true,
        ];
    }
}
