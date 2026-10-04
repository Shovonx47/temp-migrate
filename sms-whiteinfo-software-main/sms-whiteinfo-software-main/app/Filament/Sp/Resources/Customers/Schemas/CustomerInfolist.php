<?php

namespace App\Filament\Sp\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->weight('bold'),
                                TextEntry::make('email')
                                    ->copyable()
                                    ->url(fn ($state) => "mailto:{$state}"),
                                TextEntry::make('phone')
                                    ->copyable()
                                    ->url(fn ($state) => "tel:{$state}"),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('balance')
                                    ->prefix('৳')
                                    ->numeric(decimalPlaces: 2),
                                TextEntry::make('created_at')
                                    ->dateTime(),
                                TextEntry::make('updated_at')
                                    ->dateTime(),
                            ]),
                    ]),
                Section::make('Tariff Plan')
                    ->description('The retail tariff plan currently assigned to this customer.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('activeTariffAssignment.tariffPlan.name')
                                    ->label('Plan Name')
                                    ->placeholder('No plan assigned.'),
                                TextEntry::make('activeTariffAssignment.tariffPlan.type')
                                    ->label('Plan Type')
                                    ->badge()
                                    ->placeholder('—'),
                                TextEntry::make('activeTariffAssignment.effective_from')
                                    ->label('Effective From')
                                    ->date()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
