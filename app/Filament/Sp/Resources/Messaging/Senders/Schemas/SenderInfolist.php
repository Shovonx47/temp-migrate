<?php

namespace App\Filament\Sp\Resources\Messaging\Senders\Schemas;

use Filament\Infolists\Components\IconEntry;
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
                Section::make('Sender Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('sender_id')
                                    ->label('Sender ID')
                                    ->weight('bold'),
                                TextEntry::make('type')
                                    ->badge(),
                                TextEntry::make('approval_status')
                                    ->label('Approval')
                                    ->badge(),
                                TextEntry::make('gateway.name')
                                    ->label('Gateway')
                                    ->placeholder('Awaiting Superman assignment'),
                                IconEntry::make('is_enabled')
                                    ->label('Enabled')
                                    ->boolean(),
                                TextEntry::make('rejection_reason')
                                    ->label('Rejection Reason')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                                TextEntry::make('reviewed_at')
                                    ->label('Reviewed At')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
