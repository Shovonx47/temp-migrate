<?php

namespace Database\Factories;

use App\Models\SignupRegisterGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignupRegisterGateway>
 */
class SignupRegisterGatewayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'WhiteInfo Signup API',
            'endpoint_url' => 'https://sms.whiteinfobd.com/api/v1/messages',
            'http_method' => 'POST',
            'bearer_token' => 'test-signup-token',
            'sender_id' => 'FROLAX',
            'body_template' => 'Your SMS WhiteInfo verification code is: {otp}. Valid for {minutes} minutes.',
            'phone_format' => 'local_bd',
            'timeout_seconds' => 15,
            'priority' => 1,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
