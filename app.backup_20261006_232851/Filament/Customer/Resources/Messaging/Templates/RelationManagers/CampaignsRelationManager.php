<?php

namespace App\Filament\Customer\Resources\Messaging\Templates\RelationManagers;

use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CampaignsRelationManager extends RelationManager
{
    protected static string $relationship = 'campaigns';

    protected static ?string $title = 'Campaigns';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('scheduled_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn ($record) => CampaignResource::getUrl('view', ['record' => $record]));
    }
}
