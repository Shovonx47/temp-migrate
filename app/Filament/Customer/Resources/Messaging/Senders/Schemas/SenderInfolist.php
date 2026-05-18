<?php

namespace App\Filament\Customer\Resources\Messaging\Senders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SenderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Details')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('sender_id')
                            ->label('Sender ID')
                            ->weight('bold'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
