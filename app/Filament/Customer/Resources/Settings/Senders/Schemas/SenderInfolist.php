<?php

namespace App\Filament\Customer\Resources\Settings\Senders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                                TextEntry::make('sender_id')->label('Sender ID')->weight('bold'),
                                TextEntry::make('gateway.name')->label('Gateway'),
                                TextEntry::make('type')->badge(),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
