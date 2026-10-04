<?php

namespace App\Filament\Sp\Resources\Billing\TariffPlans\RelationManagers;

use App\Models\Scopes\ScopedBySp;
use App\Models\TariffPlan;
use App\Models\TariffRate;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class TariffRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'tariffRates';

    protected static ?string $title = 'Rate List';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('operator_id')
                ->label('Operator')
                ->relationship('operator', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->distinct()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
            TextInput::make('masking_price')
                ->label('Masking price (per part)')
                ->prefix('৳')
                ->prefixIcon(Heroicon::Banknotes)
                ->numeric()
                ->step('0.0001')
                ->minValue(0)
                ->nullable(),
            TextInput::make('non_masking_price')
                ->label('Non-masking price (per part)')
                ->prefix('৳')
                ->prefixIcon(Heroicon::Banknotes)
                ->numeric()
                ->step('0.0001')
                ->minValue(0)
                ->nullable(),
            DatePicker::make('effective_from')
                ->label('Effective from')
                ->nullable()
                ->helperText('Leave blank to apply immediately.'),
            Textarea::make('notes')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('operator.name')
            ->columns([
                TextColumn::make('operator.country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('masking_price')
                    ->label('Masking (per part)')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—')
                    ->color(fn (TariffRate $record): ?string => $this->isBelowWholesale($record, true) ? 'warning' : null),
                TextColumn::make('non_masking_price')
                    ->label('Non-masking (per part)')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—')
                    ->color(fn (TariffRate $record): ?string => $this->isBelowWholesale($record, false) ? 'warning' : null),
                TextColumn::make('effective_from')
                    ->label('Effective From')
                    ->date()
                    ->placeholder('Immediate'),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Rate')
                    ->after(fn (TariffRate $record) => $this->warnIfBelowWholesale($record)),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (TariffRate $record) => $this->warnIfBelowWholesale($record)),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->defaultSort('operator.name');
    }

    private function isBelowWholesale(TariffRate $rate, bool $isMasking): bool
    {
        $wholesaleRate = $this->wholesaleRates()->get($rate->operator_id);

        if (! $wholesaleRate) {
            return false;
        }

        $field = $isMasking ? 'masking_price' : 'non_masking_price';
        $retailPrice = $rate->{$field};
        $wholesalePrice = $wholesaleRate->{$field};

        return $retailPrice !== null
            && $wholesalePrice !== null
            && $retailPrice < $wholesalePrice;
    }

    private function warnIfBelowWholesale(TariffRate $rate): void
    {
        $wholesaleRate = $this->wholesaleRates()->get($rate->operator_id);

        if (! $wholesaleRate) {
            return;
        }

        $warnings = [];

        if (
            $rate->masking_price !== null
            && $wholesaleRate->masking_price !== null
            && $rate->masking_price < $wholesaleRate->masking_price
        ) {
            $warnings[] = 'Masking price (৳'.number_format((float) $rate->masking_price, 4).') is below your wholesale rate (৳'.number_format((float) $wholesaleRate->masking_price, 4).').';
        }

        if (
            $rate->non_masking_price !== null
            && $wholesaleRate->non_masking_price !== null
            && $rate->non_masking_price < $wholesaleRate->non_masking_price
        ) {
            $warnings[] = 'Non-masking price (৳'.number_format((float) $rate->non_masking_price, 4).') is below your wholesale rate (৳'.number_format((float) $wholesaleRate->non_masking_price, 4).').';
        }

        if ($warnings === []) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('Retail rate below wholesale')
            ->body(implode(' ', $warnings))
            ->persistent()
            ->send();
    }

    /**
     * @return Collection<int, TariffRate>
     */
    private function wholesaleRates(): Collection
    {
        $assignment = auth()->user()?->activeTariffAssignment;

        if (! $assignment) {
            return collect();
        }

        $plan = TariffPlan::withoutGlobalScope(ScopedBySp::class)
            ->with('tariffRates')
            ->find($assignment->tariff_plan_id);

        return $plan?->tariffRates->keyBy('operator_id') ?? collect();
    }
}
