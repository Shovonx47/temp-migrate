<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Settings\EmailGateways\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailGatewaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'asc')
            ->columns([
                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => "Priority {$state}")
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('host')
                    ->label('Host')
                    ->searchable(),

                TextColumn::make('port')
                    ->label('Port'),

                TextColumn::make('encryption')
                    ->label('Encryption')
                    ->badge(),

                TextColumn::make('from_address')
                    ->label('From Address')
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
