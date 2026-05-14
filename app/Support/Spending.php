<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\Message;
use App\Models\User;

class Spending
{
    public function __construct(
        private readonly Balance $balance,
    ) {}

    /**
     * Deduct the cost of a single message from the user's balance.
     * Throws if insufficient balance.
     */
    public function chargeForMessage(Message $message): void
    {
        if ($message->cost <= 0) {
            return;
        }

        $this->balance->debit(
            user: $message->user,
            amount: $message->cost,
            transactionable: $message,
            description: "SMS to {$message->to}",
        );
    }

    /**
     * Check if the user has enough balance to send the estimated number of messages.
     */
    public function canAffordCampaign(User $user, Campaign $campaign, int $messageCount, float $costPerMessage): bool
    {
        return $this->balance->hasBalance($user, $messageCount * $costPerMessage);
    }
}
