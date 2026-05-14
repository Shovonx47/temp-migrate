<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\RelationManagers;

use App\Filament\Customer\Resources\Messaging\DeliveryReports\DeliveryReportResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveryReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveryReports';

    protected static ?string $title = 'Delivery Reports';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gateway.name')->searchable()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('delivered_at')->dateTime()->sortable(),
                TextColumn::make('received_at')->dateTime()->sortable(),
            ])
            ->recordUrl(fn ($record) => DeliveryReportResource::getUrl('view', ['record' => $record]));
    }
}
