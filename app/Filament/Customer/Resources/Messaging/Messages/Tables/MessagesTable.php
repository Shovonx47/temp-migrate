<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('to')
                    ->label('Recipient')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money('BDT'),
                TextColumn::make('body')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->body),
                TextColumn::make('campaign.name')
                    ->placeholder('-'),
                TextColumn::make('sent_at'),
                TextColumn::make('created_at'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ]);
    }
}
