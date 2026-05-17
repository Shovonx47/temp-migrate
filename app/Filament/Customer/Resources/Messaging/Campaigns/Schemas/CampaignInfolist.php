<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Schemas;

use App\Filament\Customer\Resources\Messaging\Senders\SenderResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CampaignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign')
                    ->icon(Heroicon::Megaphone)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('name')->label('Name')->weight('bold'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('group.name')->label('Group')->icon(Heroicon::UserGroup),
                                TextEntry::make('sender.sender_id')
                                    ->label('Sender')
                                    ->url(fn ($record) => $record->sender
                                        ? SenderResource::getUrl('view', ['record' => $record->sender])
                                        : null),
                            ]),
                        TextEntry::make('body')
                            ->label('Message')
                            ->columnSpanFull(),
                    ]),

                Section::make('Recipients')
                    ->icon(Heroicon::Users)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_recipients')
                                    ->label('Total')
                                    ->numeric()
                                    ->icon(Heroicon::UserGroup),
                                TextEntry::make('total_sent')
                                    ->label('Sent')
                                    ->numeric()
                                    ->color('success')
                                    ->icon(Heroicon::CheckCircle),
                                TextEntry::make('total_failed')
                                    ->label('Failed')
                                    ->numeric()
                                    ->color('danger')
                                    ->icon(Heroicon::XCircle),
                            ]),
                    ]),

                Section::make('Timeline')
                    ->icon(Heroicon::Clock)
                    ->collapsible()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                TextEntry::make('scheduled_at')
                                    ->label('Scheduled for')
                                    ->dateTime()
                                    ->placeholder('—'),
                                TextEntry::make('started_at')
                                    ->label('Started')
                                    ->dateTime()
                                    ->placeholder('—'),
                                TextEntry::make('completed_at')
                                    ->label('Completed')
                                    ->dateTime()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
