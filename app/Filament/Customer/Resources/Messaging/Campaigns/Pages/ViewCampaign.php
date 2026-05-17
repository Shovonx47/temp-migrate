<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Pages;

use App\Enums\CampaignStatus;
use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use App\Models\Campaign;
use App\Support\CampaignDispatcher;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendNow')
                ->label('Send now')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->visible(fn (Campaign $record): bool => $record->status === CampaignStatus::Scheduled)
                ->requiresConfirmation()
                ->action(function (Campaign $record, CampaignDispatcher $dispatcher): void {
                    $dispatcher->dispatchNow($record);

                    Notification::make()
                        ->title('Campaign dispatched')
                        ->success()
                        ->send();
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->icon(Heroicon::NoSymbol)
                ->color('warning')
                ->visible(fn (Campaign $record): bool => ! $record->status->isFinal()
                    && $record->status !== CampaignStatus::Processing
                )
                ->requiresConfirmation()
                ->action(function (Campaign $record, CampaignDispatcher $dispatcher): void {
                    $cancelled = $dispatcher->cancel($record);

                    $cancelled
                        ? Notification::make()->title('Campaign cancelled')->success()->send()
                        : Notification::make()->title('Could not cancel')->warning()->send();
                }),

            DeleteAction::make(),
        ];
    }
}
