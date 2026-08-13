<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Models\Operator;
use App\Support\OperatorRateResolver;
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
        $user = auth()->user();

        /**
         * Resolve effective rates for the customer via the shared billing engine.
         * This now cascades: Active TariffPlan → Legacy user_operator_rates → Operator default.
         * The customer sees exactly what they are billed.
         */
        $resolver = OperatorRateResolver::forUser($user);

        return $table
            ->query(
                Operator::query()
                    ->active()
                    ->with('country')
            )
            ->columns([
                TextColumn::make('country.name')
                    ->label('Country'),
                TextColumn::make('name')
                    ->label('Operator'),
                TextColumn::make('codes')
                    ->label('Codes')
                    ->badge(),
                TextColumn::make('masking_rate')
                    ->label('Masking')
                    ->state(function (Operator $record) use ($resolver): string {
                        $rate = $resolver->ratesForUser(true)[$record->id] ?? null;

                        return $rate !== null ? '৳ '.number_format($rate, 4) : '—';
                    }),
                TextColumn::make('non_masking_rate')
                    ->label('Non Masking')
                    ->state(function (Operator $record) use ($resolver): string {
                        $rate = $resolver->ratesForUser(false)[$record->id] ?? null;

                        return $rate !== null ? '৳ '.number_format($rate, 4) : '—';
                    }),
            ])
            ->paginated(false);
    }
}
