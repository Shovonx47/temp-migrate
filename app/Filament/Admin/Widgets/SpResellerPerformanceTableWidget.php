<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Users\Sps\SpResource;
use App\Filament\Concerns\HasDateFilter;
use App\Models\User;
use App\Services\Analytics\SpMarginCalculationService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class SpResellerPerformanceTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'S P Performance Scorecard';

    protected ?string $pollingInterval = '300s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $selectedSpId = $this->pageFilters['sp_id'] ?? null;

        $service = app(SpMarginCalculationService::class);

        $query = User::withoutGlobalScopes()
            ->sps()
            ->whereNull('sp_id')
            ->withCount('spCustomers')
            ->withSum(
                ['spDistributions' => fn ($q) => $q->where('created_at', '>=', $from)->where('created_at', '<=', $to)],
                'amount'
            );

        if ($selectedSpId) {
            $query->where('id', $selectedSpId);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('name')
                    ->label('S P Name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (User $record): string => SpResource::getUrl('view', ['record' => $record])),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->formatStateUsing(fn ($state) => (string) $state)
                    ->searchable(),

                TextColumn::make('sp_customers_count')
                    ->label('Total Customers')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('balance')
                    ->label('Wallet Balance')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('period_messages')
                    ->label('Messages')
                    ->state(function (User $record) use ($service, $from, $to): string {
                        $data = $service->calculateForSp($record, $from, $to);

                        return number_format($data['messages']);
                    })
                    ->alignRight(),
                TextColumn::make('period_revenue')
                    ->label('Retail Spend')
                    ->state(function (User $record) use ($service, $from, $to): string {
                        $data = $service->calculateForSp($record, $from, $to);

                        return number_format($data['revenue'], 2).' BDT';
                    })
                    ->color('success')
                    ->alignRight(),
                TextColumn::make('period_wholesale')
                    ->label('Wholesale Cost')
                    ->state(function (User $record) use ($service, $from, $to): string {
                        $data = $service->calculateForSp($record, $from, $to);

                        return number_format($data['wholesale_cost'], 2).' BDT';
                    })
                    ->color('warning')
                    ->alignRight(),
                TextColumn::make('period_margin')
                    ->label('S P Margin')
                    ->state(function (User $record) use ($service, $from, $to): string {
                        $data = $service->calculateForSp($record, $from, $to);

                        return number_format($data['margin'], 2).' BDT';
                    })
                    ->color('primary')
                    ->alignRight(),
                TextColumn::make('sp_distributions_sum_amount')
                    ->label('Distributed')
                    ->money('BDT')
                    ->placeholder('0.00 BDT')
                    ->sortable()
                    ->alignRight(),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50]);
    }
}
