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

    protected static ?string $heading = 'Gateway Cost & Profit Breakdown';

    protected static ?string $description = 'Auditable Revenue, Cost, Dipping, and Profit breakdown per gateway';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        $service = app(ProfitCalculationService::class);
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);
        $breakdown = $service->getGatewayBreakdown($from, $to);
        $lookup = collect($breakdown)->keyBy('gateway_id');

        return $table
            ->query(
                Gateway::query()
                    ->whereIn('id', $lookup->keys())
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
                    ->state(fn ($record) => $lookup->get($record->id)['messages'] ?? 0)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('revenue')
                    ->label('Revenue (Tariff)')
                    ->state(fn ($record) => number_format((float) ($lookup->get($record->id)['revenue'] ?? 0), 2).' ৳')
                    ->badge()
                    ->color('success'),
                TextColumn::make('operator_cost')
                    ->label('Operator Cost')
                    ->state(fn ($record) => number_format((float) ($lookup->get($record->id)['operator_cost'] ?? 0), 2).' ৳')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('primary_dipping_cost')
                    ->label('Dipping Cost')
                    ->state(fn ($record) => number_format((float) ($lookup->get($record->id)['primary_dipping_cost'] ?? 0), 2).' ৳')
                    ->badge()
                    ->color('info'),
                TextColumn::make('third_party_dipping_cost')
                    ->label('3rd-Party Dipping')
                    ->state(function ($record) use ($lookup): string {
                        $data = $lookup->get($record->id);
                        if (! $record->has_third_party_dipping || ($data['third_party_dipping_cost'] ?? null) === null) {
                            return '—';
                        }

                        return number_format((float) $data['third_party_dipping_cost'], 2).' ৳';
                    })
                    ->placeholder('—'),
                TextColumn::make('cost')
                    ->label('Total Cost')
                    ->state(fn ($record) => number_format((float) ($lookup->get($record->id)['cost'] ?? 0), 2).' ৳')
                    ->weight('bold'),
                TextColumn::make('profit')
                    ->label('Net Profit')
                    ->state(function ($record) use ($lookup): string {
                        $data = $lookup->get($record->id);
                        if (($data['status'] ?? '') === 'incomplete') {
                            return $data['incomplete_reason'] ?? 'Incomplete';
                        }

                        $profit = $data['profit'] ?? null;
                        if ($profit === null) {
                            return '—';
                        }

                        return number_format((float) $profit, 2).' ৳';
                    })
                    ->badge(fn ($record) => ($lookup->get($record->id)['status'] ?? '') === 'incomplete')
                    ->color(fn ($record) => match (true) {
                        ($lookup->get($record->id)['status'] ?? '') === 'incomplete' => 'danger',
                        (float) ($lookup->get($record->id)['profit'] ?? 0) >= 0 => 'success',
                        default => 'danger',
                    }),
                TextColumn::make('margin')
                    ->label('Margin')
                    ->state(function ($record) use ($lookup): string {
                        $data = $lookup->get($record->id);
                        if (($data['status'] ?? '') === 'incomplete') {
                            return 'N/A';
                        }

                        return ($data['margin'] ?? 0).'%';
                    }),
                TextColumn::make('dlr_delivery_rate')
                    ->label('Delivery %')
                    ->state(function ($record) use ($lookup): string {
                        if (! $record->dlr_support) {
                            return 'N/A';
                        }
                        $metrics = $lookup->get($record->id)['dlr_metrics'] ?? null;
                        if ($metrics === null || $metrics->deliveryPercent === null) {
                            return '—';
                        }

                        return $metrics->deliveryPercent.'%';
                    })
                    ->badge(fn ($record) => ! $record->dlr_support)
                    ->color(fn ($record) => $record->dlr_support ? null : 'gray'),
            ]);
    }
}
