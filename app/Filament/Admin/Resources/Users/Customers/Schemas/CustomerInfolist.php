<?php

namespace App\Filament\Admin\Resources\Users\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
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
                Section::make('Operator rate overrides')
                    ->schema([
                        RepeatableEntry::make('operatorRates')
                            ->hiddenLabel()
                            ->placeholder('Using system defaults for every operator.')
                            ->schema([
                                TextEntry::make('operator.name')
                                    ->label('Operator'),
                                TextEntry::make('masking_price')
                                    ->label('Masking')
                                    ->prefix('৳')
                                    ->numeric(decimalPlaces: 4)
                                    ->placeholder('default'),
                                TextEntry::make('non_masking_price')
                                    ->label('Non-masking')
                                    ->prefix('৳')
                                    ->numeric(decimalPlaces: 4)
                                    ->placeholder('default'),
                            ])
                            ->columns(3),
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
