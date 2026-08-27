<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Models\User;
use Filament\Actions\Action;
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
            ->modalDescription('Specify what documents or photos the customer needs to re-upload.')
            ->schema([
                Textarea::make('reason')
                    ->label('Resubmission Instructions / Note')
                    ->placeholder('e.g., Please provide a clear, glare-free photo of the back side of your NID.')
                    ->required()
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->visible(fn (User $record) => $record->kyc_status !== KycStatus::ResubmitRequired)
            ->action(function (array $data, User $record) {
                $record->update([
                    'kyc_status' => KycStatus::ResubmitRequired,
                    'status' => UserStatus::Pending,
                    'kyc_rejected_reason' => $data['reason'],
                ]);

                Notification::make()
                    ->title('KYC Resubmission Required')
                    ->body("Please update your KYC verification: {$data['reason']}")
                    ->warning()
                    ->sendToDatabase($record);

                Notification::make()
                    ->title('Resubmission Requested')
                    ->body("Resubmission request sent to {$record->name}.")
                    ->warning()
                    ->send();
            });
    }
}
