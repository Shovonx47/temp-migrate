<?php

namespace App\Filament\Customer\Resources\Messaging\MessageBatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class MessageBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('sender.sender_id')
                    ->label('Sender ID')
                    ->searchable(),
                TextColumn::make('source_type')
                    ->label('Source')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('total_recipients')
                    ->label('Total')
                    ->abbreviate()
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('valid_count')
                    ->label('Valid')
                    ->abbreviate()
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('invalid_count')
                    ->label('Invalid')
                    ->abbreviate()
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('progressRemaining')
                    ->label('Remaining')
                    ->abbreviate()
                    ->alignRight(),
                TextColumn::make('progress_percent')
                    ->label('Progress')
                    ->suffix('%')
                    ->numeric()
                    ->alignCenter()
                    ->badge(),
                TextColumn::make('started_at'),
                TextColumn::make('completed_at'),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
