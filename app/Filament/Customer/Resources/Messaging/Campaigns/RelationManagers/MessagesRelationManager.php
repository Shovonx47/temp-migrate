<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\RelationManagers;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uid')->label('UID')->searchable()->sortable(),
                TextColumn::make('to')->label('Recipient')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('cost')->money('BDT')->sortable(),
                TextColumn::make('sent_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn ($record) => MessageResource::getUrl('view', ['record' => $record]));
    }
}
