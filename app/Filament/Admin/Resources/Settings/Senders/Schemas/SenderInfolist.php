<?php

namespace App\Filament\Admin\Resources\Settings\Senders\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;

class SenderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('users.name')
                                    ->label('Users')
                                    ->weight('bold')
                                    ->badge()
                                    ->columnSpanFull(),
                                TextEntry::make('gateway.name')
                                    ->label('Gateway'),
                                TextEntry::make('sender_id')
                                    ->label('Sender ID'),
                                TextEntry::make('type')
                                    ->badge(),
                                IconEntry::make('is_enabled')
                                    ->label('Enabled')
                                    ->boolean(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
