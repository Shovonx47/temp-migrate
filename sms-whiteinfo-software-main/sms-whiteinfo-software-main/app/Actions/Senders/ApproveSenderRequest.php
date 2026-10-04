<?php

namespace App\Actions\Senders;

use App\Enums\SenderApprovalStatus;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

readonly class ApproveSenderRequest
{
    public function handle(Sender $sender, User $reviewer, int $gatewayId): Sender
    {
        if (! $sender->approval_status?->isPending()) {
            throw new InvalidArgumentException('Only pending sender requests can be approved.');
        }

        return DB::transaction(function () use ($sender, $reviewer, $gatewayId) {
            $sender->update([
                'gateway_id' => $gatewayId,
                'approval_status' => SenderApprovalStatus::Approved,
                'is_enabled' => true,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            return $sender->fresh();
        });
    }
}
