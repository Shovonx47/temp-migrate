<?php

namespace App\Filament\Customer\Resources\Audience\Groups\RelationManagers;

use App\Filament\Customer\Resources\Audience\Contacts\ContactResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $title = 'Contacts';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->searchable()->sortable(),
                IconColumn::make('is_blocked')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn ($record) => ContactResource::getUrl('view', ['record' => $record]));
    }
}
