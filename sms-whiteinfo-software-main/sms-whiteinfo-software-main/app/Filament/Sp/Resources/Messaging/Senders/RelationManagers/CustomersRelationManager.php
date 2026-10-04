<?php

namespace App\Filament\Sp\Resources\Messaging\Senders\RelationManagers;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Customers';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('type', UserType::Customer->value)
                ->where('sp_id', auth()->id()))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('balance')
                    ->money('BDT'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Assign Customer')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->recordSelectOptionsLimit(50)
                    ->visible(fn (): bool => $this->getOwnerRecord()->isUsable())
                    ->recordSelectModifyQuery(fn (Builder $query) => $query
                        ->where('type', UserType::Customer->value)
                        ->where('sp_id', auth()->id())
                        ->where('status', UserStatus::Approved->value)),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Unassign'),
            ]);
    }
}
