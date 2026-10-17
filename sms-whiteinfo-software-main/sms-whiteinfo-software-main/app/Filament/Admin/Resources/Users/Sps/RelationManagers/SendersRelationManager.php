<?php

namespace App\Filament\Admin\Resources\Users\Sps\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SendersRelationManager extends RelationManager
{
    protected static string $relationship = 'senders';

    protected static ?string $title = 'Senders';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender_id')
                    ->label('Sender ID')
                    ->searchable(),
                TextColumn::make('gateway.name')
                    ->label('Gateway'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['sender_id'])
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                    ]),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
