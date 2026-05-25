<?php

namespace App\Filament\Customer\Resources\Messaging\Senders\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SendersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender_id')
                    ->label('Sender ID')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
            ]);
    }
}
