<?php

namespace App\Filament\Customer\Resources\Messaging\DeliveryReports\Schemas;

use App\Filament\Customer\Resources\Messaging\Messages\MessageResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('message.uid')
                                    ->label('Message')
                                    ->url(fn ($record) => $record->message
                                        ? MessageResource::getUrl('view', ['record' => $record->message])
                                        : null),
                                TextEntry::make('gateway.name')->label('Gateway'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('delivered_at')->dateTime(),
                                TextEntry::make('error_message')->color('danger')->columnSpanFull(),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
