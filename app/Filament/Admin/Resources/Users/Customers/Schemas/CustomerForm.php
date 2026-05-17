<?php

namespace App\Filament\Admin\Resources\Users\Customers\Schemas;

use App\Models\Operator;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Operator rate overrides')
                    ->description('Override the system-default operator rates for this customer. Leave a price blank to fall back to the operator default.')
                    ->schema([
                        Repeater::make('operatorRates')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Select::make('operator_id')
                                    ->label('Operator')
                                    ->options(fn () => Operator::query()
                                        ->with('country')
                                        ->get()
                                        ->mapWithKeys(fn (Operator $op): array => [
                                            $op->id => "{$op->name} ({$op->country?->name})",
                                        ])
                                        ->all()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->distinct(),
                                TextInput::make('masking_price')
                                    ->label('Masking (per part)')
                                    ->prefixIcon(Heroicon::Banknotes)
                                    ->prefix('৳')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0)
                                    ->nullable(),
                                TextInput::make('non_masking_price')
                                    ->label('Non-masking (per part)')
                                    ->prefixIcon(Heroicon::Banknotes)
                                    ->prefix('৳')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0)
                                    ->nullable(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add operator override')
                            ->collapsible(),
                    ]),
            ]);
    }
}
