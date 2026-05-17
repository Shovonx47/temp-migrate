<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Models\Operator;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class Pricing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Billing;

    protected string $view = 'filament.customer.pages.pricing';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Operator::query()
                    ->leftJoin('user_operator_rates', function ($join) {
                        $join->on('operators.id', '=', 'user_operator_rates.operator_id')
                            ->where('user_operator_rates.user_id', auth()->id());
                    })
                    ->select('operators.*')
                    ->selectRaw('COALESCE(user_operator_rates.masking_price, operators.masking_price) as effective_masking_price')
                    ->selectRaw('COALESCE(user_operator_rates.non_masking_price, operators.non_masking_price) as effective_non_masking_price')
            )
            ->columns([
                TextColumn::make('country.name')
                    ->label('Country'),
                TextColumn::make('name')
                    ->label('Operator'),
                TextColumn::make('codes')
                    ->label('Codes')
                    ->badge(),
                TextColumn::make('effective_masking_price')
                    ->label('Masking')
                    ->money('BDT'),
                TextColumn::make('effective_non_masking_price')
                    ->label('Non Masking')
                    ->money('BDT'),
            ])
            ->paginated(false);
    }
}
