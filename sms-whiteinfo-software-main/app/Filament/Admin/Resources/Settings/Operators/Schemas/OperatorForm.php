<?php

namespace App\Filament\Admin\Resources\Settings\Operators\Schemas;

use App\Models\Country;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class OperatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('country_id')
                                ->label('Country')
                                ->relationship('country', 'name')
                                ->options(fn () => Country::query()->active()->pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->preload(),
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Grameenphone'),
                            TagsInput::make('codes')
                                ->required()
                                ->placeholder('17, 13')
                                ->helperText('Operator codes used in phone numbers (digits only, e.g. 17 for GP).')
                                ->columnSpanFull(),
                            Toggle::make('is_active')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),
                Section::make('Default rates')
                    ->description('Per-SMS-part rate. Leave blank to require a per-user override before pricing.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('masking_price')
                                ->label('Masking price (per part)')
                                ->prefixIcon(Heroicon::Banknotes)
                                ->prefix('৳')
                                ->numeric()
                                ->step('0.0001')
                                ->minValue(0)
                                ->nullable(),
                            TextInput::make('non_masking_price')
                                ->label('Non-masking price (per part)')
                                ->prefixIcon(Heroicon::Banknotes)
                                ->prefix('৳')
                                ->numeric()
                                ->step('0.0001')
                                ->minValue(0)
                                ->nullable(),
                        ]),
                    ]),
            ]);
    }
}
