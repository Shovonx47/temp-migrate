<?php

namespace App\Filament\Admin\Resources\Operators\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OperatorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('country.name')->label('Country'),
                            TextEntry::make('name')->weight('bold'),
                            TextEntry::make('codes')
                                ->state(fn ($record): string => implode(', ', (array) $record->codes))
                                ->columnSpanFull(),
                            IconEntry::make('is_active')->boolean(),
                        ]),
                    ]),
                Section::make('Default rates')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('masking_price')
                                ->label('Masking (per part)')
                                ->prefix('৳')
                                ->numeric(decimalPlaces: 4)
                                ->placeholder('—'),
                            TextEntry::make('non_masking_price')
                                ->label('Non-masking (per part)')
                                ->prefix('৳')
                                ->numeric(decimalPlaces: 4)
                                ->placeholder('—'),
                        ]),
                    ]),
            ]);
    }
}
