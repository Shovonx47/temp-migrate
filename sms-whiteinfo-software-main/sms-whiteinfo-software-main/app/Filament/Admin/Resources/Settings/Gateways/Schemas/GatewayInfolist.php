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
                                TextEntry::make('route_code')
                                    ->label('Route Code')
                                    ->badge()
                                    ->placeholder('—'),
                                TextEntry::make('driver')
                                    ->badge(),
                                TextEntry::make('requests_per_second')
                                    ->label('RPS')
                                    ->columnSpanFull(),
                                IconEntry::make('is_enabled')
                                    ->label('Enabled')
                                    ->boolean(),
                                IconEntry::make('supports_bulk')
                                    ->label('Supports Promotional')
                                    ->boolean(),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make('Billing & Cost Configuration')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                IconEntry::make('dlr_support')
                                    ->label('DLR Support')
                                    ->boolean(),
                                TextEntry::make('cost_mode')
                                    ->label('Cost Mode')
                                    ->badge(),
                                IconEntry::make('dipping_required')
                                    ->label('Mandatory Dipping')
                                    ->boolean(),
                                IconEntry::make('has_third_party_dipping')
                                    ->label('3rd-Party Dipping')
                                    ->boolean(),
                            ]),
                    ]),
            ]);
    }
}
