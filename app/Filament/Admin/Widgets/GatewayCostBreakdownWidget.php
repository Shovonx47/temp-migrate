<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Gateway;
use App\Services\Analytics\ProfitCalculationService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class GatewayCostBreakdownWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Gateway Cost Breakdown';

    protected static ?string $description = 'Revenue, cost, and profit per gateway';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        $service = app(ProfitCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $breakdown = $service->getGatewayBreakdown($from, $to);

        return $table
            ->query(
                Gateway::query()
                    ->whereIn('name', array_column($breakdown, 'gateway'))
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Gateway')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('driver')
                    ->badge(),
                TextColumn::make('messages')
                    ->label('Messages')
                    ->state(fn ($record) => collect($breakdown)->firstWhere('gateway', $record->name)['messages'] ?? 0)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->state(fn ($record) => number_format(collect($breakdown)->firstWhere('gateway', $record->name)['revenue'] ?? 0, 2).' BDT'),
                TextColumn::make('cost')
                    ->label('Gateway Cost')
                    ->state(fn ($record) => number_format(collect($breakdown)->firstWhere('gateway', $record->name)['cost'] ?? 0, 2).' BDT'),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->state(fn ($record) => number_format(collect($breakdown)->firstWhere('gateway', $record->name)['profit'] ?? 0, 2).' BDT'),
                TextColumn::make('margin')
                    ->label('Margin')
                    ->state(fn ($record) => (collect($breakdown)->firstWhere('gateway', $record->name)['margin'] ?? 0).'%'),
            ]);
    }
}
