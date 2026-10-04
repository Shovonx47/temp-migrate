<?php

namespace App\Filament\Customer\Resources\Reports\Exports\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->searchable(),
                TextColumn::make('processed_rows')
                    ->label('Processed')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_rows')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('successful_rows')
                    ->label('Successful')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->placeholder('Processing'),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),
            ]);
    }
}
