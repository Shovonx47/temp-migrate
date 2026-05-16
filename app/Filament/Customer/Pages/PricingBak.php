<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Models\Operator;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PricingBak extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyBangladeshi;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Pricing';

    protected static ?string $title = 'Pricing';

    protected ?string $heading = 'Your SMS pricing';

    protected ?string $subheading = 'Per-part rates by operator. Overrides take precedence over operator defaults.';

    protected string $view = 'filament.customer.pages.pricing-bak';

    protected static ?string $slug = 'pricing-bak';

    /**
     * @return array<int, array{
     *     country: string,
     *     name: string,
     *     codes: string,
     *     masking: ?float,
     *     non_masking: ?float,
     *     overridden_masking: bool,
     *     overridden_non_masking: bool,
     * }>
     */
    public function getRowsProperty(): array
    {
        $user = auth()->user();

        $overrides = $user->operatorRates()
            ->get(['operator_id', 'masking_price', 'non_masking_price'])
            ->keyBy('operator_id');

        return Operator::query()
            ->active()
            ->with('country')
            ->orderBy('country_id')
            ->orderBy('name')
            ->get()
            ->map(function (Operator $operator) use ($overrides): array {
                $override = $overrides->get($operator->id);

                $masking = $override?->masking_price ?? $operator->masking_price;
                $nonMasking = $override?->non_masking_price ?? $operator->non_masking_price;

                return [
                    'country' => $operator->country?->name ?? '—',
                    'name' => $operator->name,
                    'codes' => implode(', ', (array) $operator->codes),
                    'masking' => $masking !== null ? (float) $masking : null,
                    'non_masking' => $nonMasking !== null ? (float) $nonMasking : null,
                    'overridden_masking' => $override?->masking_price !== null,
                    'overridden_non_masking' => $override?->non_masking_price !== null,
                ];
            })
            ->all();
    }
}
