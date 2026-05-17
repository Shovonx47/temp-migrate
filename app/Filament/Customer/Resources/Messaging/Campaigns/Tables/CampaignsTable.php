<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Tables;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\Sms\Campaigns\CampaignDispatcher;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sender.sender_id')
                    ->label('Sender')
                    ->searchable(),
                TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('total_sent')
                    ->label('Sent')
                    ->alignRight()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('total_failed')
                    ->label('Failed')
                    ->alignRight()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(CampaignStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

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
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
