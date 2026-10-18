<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Operator;
use App\Services\Analytics\ProfitCalculationService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class OperatorCostBreakdownWidget extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Operator Cost Breakdown';

    protected static ?string $description = 'Revenue and cost per operator';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        $service = app(ProfitCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $breakdown = $service->getOperatorBreakdown($from, $to);

        return $table
            ->query(
                Operator::query()
                    ->whereIn('name', collect($breakdown)->pluck('operator')->filter()->values())
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Operator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country.name')
                    ->label('Country'),
                TextColumn::make('messages')
                    ->label('Messages')
                    ->state(fn ($record) => collect($breakdown)->firstWhere('operator', $record->name)['messages'] ?? 0)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->state(fn ($record) => number_format(collect($breakdown)->firstWhere('operator', $record->name)['revenue'] ?? 0, 2).' BDT'),
                TextColumn::make('operator_cost')
                    ->label('Operator Cost')
                    ->state(fn ($record) => number_format(collect($breakdown)->firstWhere('operator', $record->name)['operator_cost'] ?? 0, 2).' BDT'),
                TextColumn::make('gateway_cost')
                    ->label('Gateway Cost')
                    ->state(fn ($record) => number_format(collect($breakdown)->firstWhere('operator', $record->name)['gateway_cost'] ?? 0, 2).' BDT'),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->state(fn ($record) => number_format(collect($breakdown)->firstWhere('operator', $record->name)['profit'] ?? 0, 2).' BDT'),
            ]);
    }
}
