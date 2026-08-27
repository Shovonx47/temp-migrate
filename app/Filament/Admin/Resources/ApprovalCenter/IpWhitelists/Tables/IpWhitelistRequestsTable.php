<?php

namespace App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Tables;

use App\Enums\IpWhitelistStatus;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions\ApproveIpAction;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions\RejectIpAction;
use App\Filament\Admin\Resources\ApprovalCenter\IpWhitelists\Actions\RevokeIpAction;
use App\Models\IpWhitelistRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IpWhitelistRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (IpWhitelistRequest $record): ?string => $record->user_id ? url("/superman/users/customers/{$record->user_id}") : null),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->weight('medium')
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Label')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('admin_note')
                    ->label('Admin Remark')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn (IpWhitelistRequest $record): ?string => $record->admin_note)
                    ->toggleable(),

                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(IpWhitelistStatus::class)
                    ->default(IpWhitelistStatus::Pending->value),
            ])
            ->recordActions([
                ApproveIpAction::make(),
                RejectIpAction::make(),
                RevokeIpAction::make(),
            ])
            ->toolbarActions([]);
    }
}
