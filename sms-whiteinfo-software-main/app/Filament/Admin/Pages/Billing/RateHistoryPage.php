<?php

namespace App\Filament\Admin\Pages\Billing;

use App\Enums\SupermanNavigationGroup;
use App\Models\TariffPlan;
use App\Models\TariffRateHistory;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class RateHistoryPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Rate History';

    protected static ?string $title = 'Rate Change History';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.admin.billing.rate-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(TariffRateHistory::with(['tariffPlan', 'operator', 'changedBy']))
            ->columns([
                TextColumn::make('tariffPlan.name')
                    ->label('Plan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('old_masking_price')
                    ->label('Old Masking')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—'),
                TextColumn::make('new_masking_price')
                    ->label('New Masking')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—'),
                TextColumn::make('old_non_masking_price')
                    ->label('Old Non-masking')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—'),
                TextColumn::make('new_non_masking_price')
                    ->label('New Non-masking')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—'),
                TextColumn::make('changedBy.name')
                    ->label('Changed By')
                    ->placeholder('System'),
                TextColumn::make('changed_at')
                    ->label('Changed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tariff_plan_id')
                    ->label('Plan')
                    ->options(TariffPlan::pluck('name', 'id')),
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->defaultSort('changed_at', 'desc');
    }
}
