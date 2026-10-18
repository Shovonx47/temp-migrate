<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use App\Enums\KycPermission;
use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Models\KycAuditLog;
use App\Models\User;
use App\Services\KycGate;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ApproveKycAction
{
    public static function make(?string $name = 'approve_kyc'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedCheckBadge)
            ->label('Approve KYC')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Approve Customer KYC')
            ->modalDescription("Are you sure you want to approve this customer's KYC documents and activate their account?")
            ->visible(fn (User $record) =>
                $record->kyc_status !== KycStatus::Verified &&
                KycGate::allows(KycPermission::Approve)
            )
            ->action(function (User $record) {
                $oldStatus = $record->kyc_status?->value;

                $record->update([
                    'kyc_status'           => KycStatus::Verified,
                    'status'               => UserStatus::Approved,
                    'nid_verified'         => true,
                    'live_photo_verified'  => true,
                    'kyc_verified_at'      => now(),
                    'kyc_rejected_reason'  => null,
                    'kyc_resubmit_fields'  => null,
                ]);

                // Audit log
                KycAuditLog::record(
                    customer: $record,
                    action: 'approved',
                    fieldName: 'kyc_status',
                    oldValue: $oldStatus,
                    newValue: KycStatus::Verified->value,
                );

                // Notify the customer
                Notification::make()
                    ->title('KYC Approved')
                    ->body('Your KYC documents have been verified and your account is now approved.')
                    ->success()
                    ->sendToDatabase($record);

                // Notify the admin
                Notification::make()
                    ->title('KYC Approved Successfully')
                    ->body("Customer {$record->name}'s KYC has been approved.")
                    ->success()
                    ->send();
            });
    }
}
