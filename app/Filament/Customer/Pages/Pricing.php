<?php

namespace App\Filament\Customer\Pages;

use App\Models\Operator;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class Pricing extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected string $view = 'filament.customer.pages.pricing';

    public function table(Table $table): Table
    {
        return $table
            ->query(Operator::query())
            ->columns([
                TextColumn::make('country.name')
                    ->label('Country'),
                TextColumn::make('name')
                    ->label('Operator'),
                TextColumn::make('codes')
                    ->label('Codes')
                    ->badge(),
                TextColumn::make('masking_price')
                    ->label('Masking')
                    ->money('BDT'),
                TextColumn::make('non_masking_price')
                    ->label('Non Masking')
                    ->money('BDT'),
            ]);
    }
}
