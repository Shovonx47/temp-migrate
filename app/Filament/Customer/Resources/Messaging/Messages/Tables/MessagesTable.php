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
                TextColumn::make('uid')->label('UID')->searchable()->sortable(),
                TextColumn::make('campaign.name')->searchable()->sortable(),
                TextColumn::make('to')->label('Recipient')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('cost')->money('BDT')->sortable(),
                TextColumn::make('segments')->numeric()->sortable(),
                TextColumn::make('sent_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ]);
    }
}
