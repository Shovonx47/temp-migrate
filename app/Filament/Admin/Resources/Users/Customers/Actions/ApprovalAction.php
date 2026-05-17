<?php

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use App\Enums\UserApproval;
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
            ->visible(fn (User $record) => $record->approval->isPending())
            ->fillForm(fn (User $record) => [
                'approval' => $record->approval,
            ])
            ->schema([
                Select::make('approval')
                    ->label('Approval Status')
                    ->options(UserApproval::class),
            ])
            ->action(function (array $data, User $record) {
                $record->update(['approval' => $data['approval']]);

                if ($record->wasChanged('approval')) {
                    Notification::make()
                        ->title('Approval Status Updated')
                        ->body("Your approval status has been updated to {$record->approval->value}.")
                        ->success()
                        ->sendToDatabase($record);

                    Notification::make()
                        ->title('Approval Updated')
                        ->body("Customer approval status has been updated to {$record->approval->value}.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('No Changes Made')
                        ->body("The approval status is already set to {$record->approval->value}.")
                        ->warning()
                        ->send();
                }
            });
    }
}
