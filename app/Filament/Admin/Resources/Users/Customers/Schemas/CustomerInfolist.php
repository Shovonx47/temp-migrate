<?php

namespace App\Filament\Admin\Resources\Users\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
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
                Section::make('SMS Permissions')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                IconEntry::make('api_access')
                                    ->label('API Access')
                                    ->boolean(),
                                IconEntry::make('sms_auto_approved')
                                    ->label('SMS Auto-approved')
                                    ->boolean(),
                            ]),
                    ]),
                Section::make('Tariff Plan')
                    ->description('The tariff plan currently assigned to this customer.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('activeTariffAssignment.tariffPlan.name')
                                    ->label('Plan Name')
                                    ->placeholder('No plan assigned — using operator defaults.'),
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
                Section::make()
                    ->heading('KYC Information')
                    ->columns()
                    ->schema([
                        ImageEntry::make('front_nid')
                            ->label('Front Side of NID'),
                        ImageEntry::make('back_nid')
                            ->label('Back Side of NID'),
                    ]),
            ]);
    }
}
