<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GatewayInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextEntry::make('name')
                                    ->weight('bold'),
                                TextEntry::make('driver')
                                    ->badge(),
                                TextEntry::make('requests_per_minute')
                                    ->label('RPM')
                                    ->columnSpanFull(),
                                IconEntry::make('is_enabled')
                                    ->label('Enabled')
                                    ->boolean(),
                                IconEntry::make('supports_bulk')
                                    ->label('Supports Bulk')
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
