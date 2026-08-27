<?php

namespace App\Filament\Customer\Resources\Developer\IpWhitelists\Tables;

use App\Models\IpWhitelistRequest;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IpWhitelistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
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
                    ->color('danger')
                    ->limit(50)
                    ->tooltip(fn (IpWhitelistRequest $record): ?string => $record->admin_note),

                TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label('Reviewed At')
                    ->dateTime('M d, Y \a\t g:i A')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove IP Whitelist Entry')
                    ->modalDescription('Are you sure you want to remove this IP address? If approved, API requests from this IP will no longer be authorized.'),
            ])
            ->toolbarActions([]);
    }
}
