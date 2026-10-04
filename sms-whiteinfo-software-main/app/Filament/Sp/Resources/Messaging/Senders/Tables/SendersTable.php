<?php

namespace App\Filament\Sp\Resources\Messaging\Senders\Tables;

use App\Enums\SenderApprovalStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SendersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'users as customers_count' => fn ($q) => $q
                    ->where('type', 'customer')
                    ->where('sp_id', auth()->id()),
            ]))
            ->columns([
                TextColumn::make('sender_id')
                    ->label('Sender ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('approval_status')
                    ->label('Approval')
                    ->badge()
                    ->sortable(),
                TextColumn::make('gateway.name')
                    ->label('Gateway')
                    ->placeholder('Awaiting assignment'),
                IconColumn::make('is_enabled')
                    ->label('Enabled')
                    ->boolean(),
                TextColumn::make('customers_count')
                    ->label('Customers')
                    ->alignCenter(),
                TextColumn::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('Approval')
                    ->options(SenderApprovalStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
