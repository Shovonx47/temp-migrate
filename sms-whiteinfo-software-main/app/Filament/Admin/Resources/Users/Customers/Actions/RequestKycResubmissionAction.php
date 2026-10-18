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
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class RequestKycResubmissionAction
{
    public static function make(?string $name = 'request_kyc_resubmission'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedArrowPath)
            ->label('Request Resubmission')
            ->color('warning')
            ->modalHeading('Request KYC Resubmission')
            ->modalDescription('Select the specific field(s) the customer needs to correct, then provide instructions.')
            ->schema([
                CheckboxList::make('resubmit_fields')
                    ->label('Fields Requiring Correction')
                    ->options([
                        'nid_number'    => 'NID Number',
                        'nid_front'     => 'NID Front Photo',
                        'nid_back'      => 'NID Back Photo',
                        'live_photo'    => 'Live Selfie Photo',
                        'trade_license' => 'Trade License',
                        'other'         => 'Other (see note below)',
                    ])
                    ->required()
                    ->columns(2)
                    ->helperText('Only the selected fields will be shown to the customer for resubmission.'),

                Textarea::make('reason')
                    ->label('Resubmission Instructions / Note to Customer')
                    ->placeholder('e.g., The back of your NID card is blurry. Please upload a clear, well-lit photo.')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->visible(fn (User $record) =>
                $record->kyc_status !== KycStatus::ResubmitRequired &&
                KycGate::allows(KycPermission::ResubmissionRequest)
            )
            ->action(function (array $data, User $record) {
                $oldStatus = $record->kyc_status?->value;

                $record->update([
                    'kyc_status'           => KycStatus::ResubmitRequired,
                    'status'               => UserStatus::Pending,
                    'kyc_rejected_reason'  => $data['reason'],
                    'kyc_resubmit_fields'  => $data['resubmit_fields'],
                ]);

                // Audit log — one entry for the status change
                KycAuditLog::record(
                    customer: $record,
                    action: 'resubmission_requested',
                    fieldName: 'kyc_status',
                    oldValue: $oldStatus,
                    newValue: KycStatus::ResubmitRequired->value,
                    note: 'Fields: '.implode(', ', $data['resubmit_fields']).'. Reason: '.$data['reason'],
                );

                // Notify the customer
                Notification::make()
                    ->title('KYC Resubmission Required')
                    ->body("Please update and resubmit your KYC: {$data['reason']}")
                    ->warning()
                    ->sendToDatabase($record);

                // Notify the admin who triggered this
                Notification::make()
                    ->title('Resubmission Requested')
                    ->body("Resubmission request sent to {$record->name}.")
                    ->warning()
                    ->send();
            });
    }
}
