<?php

namespace App\Filament\Admin\Resources\Users\Sps\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DistributionsRelationManager extends RelationManager
{
    protected static string $relationship = 'spDistributions';

    protected static ?string $title = 'Distribution History';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('BDT'),
                TextColumn::make('note')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('distributedBy.name')
                    ->label('Distributed By')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([]);
    }
}
