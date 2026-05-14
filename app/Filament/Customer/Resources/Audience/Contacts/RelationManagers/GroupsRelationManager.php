<?php

namespace App\Filament\Customer\Resources\Audience\Contacts\RelationManagers;

use App\Filament\Customer\Resources\Audience\Groups\GroupResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static ?string $title = 'Groups';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn ($record) => GroupResource::getUrl('view', ['record' => $record]));
    }
}
