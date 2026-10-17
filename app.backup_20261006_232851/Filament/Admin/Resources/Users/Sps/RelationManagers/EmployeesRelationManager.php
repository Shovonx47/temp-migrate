<?php

namespace App\Filament\Admin\Resources\Users\Sps\RelationManagers;

use App\Enums\UserStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'spEmployees';

    protected static ?string $title = 'Employees';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== UserStatus::Banned)
                    ->action(function ($record): void {
                        $record->update(['status' => UserStatus::Banned]);
                        Notification::make()
                            ->title('S P Employee suspended')
                            ->success()
                            ->send();
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== UserStatus::Approved)
                    ->action(function ($record): void {
                        $record->update(['status' => UserStatus::Approved]);
                        Notification::make()
                            ->title('S P Employee approved')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
