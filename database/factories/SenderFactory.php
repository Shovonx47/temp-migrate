<?php

namespace Database\Factories;

use App\Enums\SenderApprovalStatus;
use App\Enums\SenderType;
use App\Models\Gateway;
use App\Models\Sender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sender>
 */
class SenderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gateway_id' => Gateway::factory(),
            'sender_id' => fake()->unique()->regexify('[A-Za-z0-9]{8}'),
            'type' => fake()->randomElement(SenderType::cases()),
            'is_enabled' => false,
            'approval_status' => SenderApprovalStatus::Approved,
            'requested_by' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => true,
            'approval_status' => SenderApprovalStatus::Approved,
        ]);
    }

    public function pendingRequest(?int $requestedBy = null): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_id' => null,
            'is_enabled' => false,
            'approval_status' => SenderApprovalStatus::Pending,
            'requested_by' => $requestedBy,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);
    }
}
