<?php

namespace App\Actions\Senders;

use App\Enums\SenderApprovalStatus;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

readonly class RejectSenderRequest
{
    public function handle(Sender $sender, User $reviewer, string $reason): Sender
    {
        if (! $sender->approval_status?->isPending()) {
            throw new InvalidArgumentException('Only pending sender requests can be rejected.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($sender, $reviewer, $reason) {
            $sender->update([
                'approval_status' => SenderApprovalStatus::Rejected,
                'is_enabled' => false,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $sender->fresh();
        });
    }
}
