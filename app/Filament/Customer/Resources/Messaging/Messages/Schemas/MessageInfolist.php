<?php

namespace App\Filament\Customer\Resources\Messaging\Messages\Schemas;

use App\Filament\Customer\Resources\Messaging\Campaigns\CampaignResource;
use App\Filament\Customer\Resources\Messaging\Senders\SenderResource;
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
                                TextEntry::make('uid')
                                    ->label('UID')
                                    ->copyable()
                                    ->weight('bold'),
                                TextEntry::make('campaign.name')
                                    ->label('Campaign')
                                    ->url(fn ($record) => $record->campaign
                                        ? CampaignResource::getUrl('view', ['record' => $record->campaign])
                                        : null),
                                TextEntry::make('sender.sender_id')
                                    ->label('Sender')
                                    ->url(fn ($record) => $record->sender
                                        ? SenderResource::getUrl('view', ['record' => $record->sender])
                                        : null),
                                TextEntry::make('to')
                                    ->label('Recipient'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('cost')
                                    ->money('BDT'),
                                TextEntry::make('parts')
                                    ->numeric(),
                                TextEntry::make('sent_at')
                                    ->dateTime(),
                                TextEntry::make('body')
                                    ->columnSpanFull(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
