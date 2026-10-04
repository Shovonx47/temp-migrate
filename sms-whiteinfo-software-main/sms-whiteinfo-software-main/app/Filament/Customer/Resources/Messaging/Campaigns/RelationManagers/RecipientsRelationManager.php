<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\RelationManagers;

use App\Enums\CampaignRecipientStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Recipients';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('phone')
            ->columns([
                TextColumn::make('contact.name')
                    ->label('Name')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('cost')
                    ->money('BDT')
                    ->placeholder('—')
                    ->alignRight(),
                TextColumn::make('sent_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('error')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(CampaignRecipientStatus::class),
            ]);
    }
}
