<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class CustomerUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        $status = $user->status instanceof UserStatus ? $user->status->value : (string) $user->status;
        $kycStatus = $user->kyc_status instanceof KycStatus ? $user->kyc_status->value : (string) $user->kyc_status;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'balance' => number_format((float) $user->balance, 2, '.', ''),
            'status' => $status,
            'kyc_status' => $kycStatus,
            'kyc_rejected_reason' => $user->kyc_rejected_reason,
            'kyc_resubmit_fields' => $user->kyc_resubmit_fields ?? [],
            'created_by_admin' => (bool) $user->created_by_admin,
            'has_nid' => filled($user->front_nid),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
