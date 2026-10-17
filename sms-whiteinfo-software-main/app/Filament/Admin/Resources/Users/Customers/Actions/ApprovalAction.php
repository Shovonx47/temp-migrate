<?php

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ApprovalAction
{
    public static function make(?string $name = 'approval'): Action
    {
        return Action::make($name)
            ->icon(Heroicon::OutlinedCheckCircle)
            ->label('Edit Approval')
            ->modalHeading('Approval')
            ->modalDescription('Change the approval status of the customer.')
            ->modalWidth(Width::Medium)
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (User $record) => $record->status->isPending())
            ->fillForm(fn (User $record) => [
                'approval' => $record->status,
            ])
            ->schema([
                Select::make('approval')
                    ->label('Approval Status')
                    ->options(UserStatus::class),
            ])
            ->action(function (array $data, User $record) {
                $record->update(['status' => $data['approval']]);

                if ($record->wasChanged('status')) {
                    Notification::make()
                        ->title('Approval Status Updated')
                        ->body("Your approval status has been updated to {$record->status->value}.")
                        ->success()
                        ->sendToDatabase($record);

                    Notification::make()
                        ->title('Approval Updated')
                        ->body("Customer approval status has been updated to {$record->status->value}.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('No Changes Made')
                        ->body("The approval status is already set to {$record->status->value}.")
                        ->warning()
                        ->send();
                }
            });
    }
}
