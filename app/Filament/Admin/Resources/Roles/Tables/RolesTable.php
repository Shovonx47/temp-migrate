<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->placeholder('No description'),

                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Assigned Staff')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('permissions')
                    ->label('Permissions')
                    ->state(fn ($record): string => count($record->permissions ?? []).' granted')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
