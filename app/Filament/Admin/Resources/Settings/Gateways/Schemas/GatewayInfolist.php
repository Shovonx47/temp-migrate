<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Schemas;

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
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')->weight('bold'),
                                TextEntry::make('driver')->badge(),
                                TextEntry::make('is_active')->badge(),
                                TextEntry::make('is_default')->badge(),
                                TextEntry::make('limit_per_second'),
                                TextEntry::make('limit_per_minute'),
                                TextEntry::make('created_at')->dateTime(),
                                TextEntry::make('updated_at')->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
