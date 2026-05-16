<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Models\Operator;
use BackedEnum;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class Pricing extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyBangladeshi;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Pricing';

    protected static ?string $title = 'Pricing';

    protected ?string $heading = 'Your SMS pricing';

    protected ?string $subheading = 'Per-part rates by operator. Overrides take precedence over operator defaults.';

    protected string $view = 'filament.customer.pages.pricing';

    public function table(Table $table): Table
    {
        $userId = auth()->id();

        return $table
            ->query(
                Operator::query()
                    ->select([
                        'operators.id',
                        'operators.name',
                        'operators.codes',
                        'operators.masking_price as default_masking',
                        'operators.non_masking_price as default_non_masking',
                        'countries.name as country_name',
                        'user_operator_rates.masking_price as override_masking',
                        'user_operator_rates.non_masking_price as override_non_masking',
                    ])
                    ->join('countries', 'countries.id', '=', 'operators.country_id')
                    ->leftJoin('user_operator_rates', function ($join) use ($userId): void {
                        $join->on('user_operator_rates.operator_id', '=', 'operators.id')
                            ->where('user_operator_rates.user_id', '=', $userId);
                    })
                    ->where('operators.is_active', true)
                    ->where('countries.is_active', true)
                    ->orderBy('countries.name')
                    ->orderBy('operators.name'),
            )
            ->columns([
                TextColumn::make('country_name')->label('Country')->sortable(),
                TextColumn::make('name')->label('Operator')->sortable(),
                TextColumn::make('codes')
                    ->label('Codes')
                    ->state(fn (Operator $record): string => implode(', ', (array) $record->codes)),
                TextColumn::make('non_masking_effective')
                    ->label('Non-masking (per part)')
                    ->state(fn (Operator $record): ?float => self::effective($record->override_non_masking, $record->default_non_masking))
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('—')
                    ->description(fn (Operator $record): ?string => $record->override_non_masking !== null ? 'override' : null),
                TextColumn::make('masking_effective')
                    ->label('Masking (per part)')
                    ->state(fn (Operator $record): ?float => self::effective($record->override_masking, $record->default_masking))
                    ->prefix('৳')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('—')
                    ->description(fn (Operator $record): ?string => $record->override_masking !== null ? 'override' : null),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->paginated(false);
    }

    private static function effective(mixed $override, mixed $default): ?float
    {
        $value = $override ?? $default;

        return $value === null ? null : (float) $value;
    }
}
