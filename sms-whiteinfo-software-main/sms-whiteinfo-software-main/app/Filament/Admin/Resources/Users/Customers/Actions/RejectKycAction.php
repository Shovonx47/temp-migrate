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
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class RejectKycAction
{
    public static function make(?string $name = 'reject_kyc'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedXCircle)
            ->label('Reject KYC')
            ->color('danger')
            ->modalHeading('Reject Customer KYC')
            ->modalDescription("Specify the reason for rejecting this customer's KYC application.")
            ->schema([
                Textarea::make('reason')
                    ->label('Rejection Reason')
                    ->placeholder('e.g., NID front photo is blurry and illegible.')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),

                Textarea::make('admin_remarks')
                    ->label('Admin Remarks (Internal)')
                    ->placeholder('Internal notes — not shown to the customer.')
                    ->rows(2)
                    ->maxLength(1000),
            ])
            ->visible(fn (User $record) =>
                $record->kyc_status !== KycStatus::Rejected &&
                KycGate::allows(KycPermission::Reject)
            )
            ->action(function (array $data, User $record) {
                $oldStatus = $record->kyc_status?->value;

                $record->update([
                    'kyc_status'          => KycStatus::Rejected,
                    'status'              => UserStatus::Rejected,
                    'kyc_rejected_reason' => $data['reason'],
                ]);

                // Audit log
                KycAuditLog::record(
                    customer: $record,
                    action: 'rejected',
                    fieldName: 'kyc_status',
                    oldValue: $oldStatus,
                    newValue: KycStatus::Rejected->value,
                    note: 'Reason: '.$data['reason'].($data['admin_remarks'] ? ' | Remarks: '.$data['admin_remarks'] : ''),
                );

                // Notify the customer
                Notification::make()
                    ->title('KYC Application Rejected')
                    ->body("Your KYC submission was rejected: {$data['reason']}")
                    ->danger()
                    ->sendToDatabase($record);

                // Notify the admin
                Notification::make()
                    ->title('KYC Rejected')
                    ->body("Customer {$record->name}'s KYC has been rejected.")
                    ->danger()
                    ->send();
            });
    }
}
