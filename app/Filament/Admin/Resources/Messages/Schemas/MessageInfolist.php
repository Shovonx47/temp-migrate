<?php

namespace App\Filament\Admin\Resources\Messages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('uid')->label('UID')->copyable(),
                                TextEntry::make('campaign.name')->label('Campaign'),
                                TextEntry::make('user.name')->label('User'),
                                TextEntry::make('sender.sender_id')->label('Sender ID'),
                                TextEntry::make('to')->label('Recipient'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('cost')->money('BDT'),
                                TextEntry::make('segments')->numeric(),
                                TextEntry::make('sent_at')->dateTime(),
                                TextEntry::make('delivered_at')->dateTime(),
                                TextEntry::make('body')->columnSpanFull(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
