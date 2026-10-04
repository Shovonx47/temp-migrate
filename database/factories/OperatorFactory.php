<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Operator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Operator>
 */
class OperatorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => fake()->company(),
            'codes' => [fake()->unique()->numberBetween(10, 99)],
            'masking_price' => 0.50,
            'non_masking_price' => 0.30,
            'is_active' => true,
        ];
    }
}
