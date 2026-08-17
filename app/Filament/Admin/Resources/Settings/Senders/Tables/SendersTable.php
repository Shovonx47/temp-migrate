<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Tables;

use App\Enums\SenderApprovalStatus;
use App\Filament\Admin\Resources\Settings\Senders\Actions\ApproveSenderAction;
use App\Filament\Admin\Resources\Settings\Senders\Actions\RejectSenderAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SendersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('gateway.name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('sender_id')
                    ->icon(Heroicon::OutlinedClipboard)
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('approval_status')
                    ->label('Approval')
                    ->badge()
                    ->sortable(),
                TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->placeholder('Admin')
                    ->toggleable(),
                TextColumn::make('users.name')
                    ->label('Assigned Users')
                    ->searchable()
                    ->badge(),
                ToggleColumn::make('is_enabled')
                    ->disabled(fn ($record) => $record->approval_status !== SenderApprovalStatus::Approved),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('Approval')
                    ->options(SenderApprovalStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    ApproveSenderAction::make(),
                    RejectSenderAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
