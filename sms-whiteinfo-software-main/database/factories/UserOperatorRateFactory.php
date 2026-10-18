<?php

namespace Database\Factories;

use App\Models\Operator;
use App\Models\User;
use App\Models\UserOperatorRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserOperatorRate>
 */
class UserOperatorRateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'operator_id' => Operator::factory(),
            'masking_price' => null,
            'non_masking_price' => null,
        ];
    }
}
