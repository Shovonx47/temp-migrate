<?php

namespace App\Filament\Customer\Resources\Messaging\Campaigns\Schemas;

use App\Filament\Customer\Resources\Messaging\Templates\TemplateResource;
use App\Filament\Customer\Resources\Settings\Senders\SenderResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')->label('Campaign Name')->weight('bold'),
                                TextEntry::make('template.name')
                                    ->label('Template')
                                    ->url(fn ($record) => $record->template
                                        ? TemplateResource::getUrl('view', ['record' => $record->template])
                                        : null),
                                TextEntry::make('sender.sender_id')
                                    ->label('Sender')
                                    ->url(fn ($record) => $record->sender
                                        ? SenderResource::getUrl('view', ['record' => $record->sender])
                                        : null),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('scheduled_at')->dateTime(),
                                TextEntry::make('started_at')->dateTime(),
                                TextEntry::make('completed_at')->dateTime(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
