<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Gateway;
use App\Services\Analytics\ProfitCalculationService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class GatewayCostBreakdownWidget extends TableWidget
{
    protected static ?string $heading = 'Gateway Cost Breakdown';

    protected static ?string $description = 'Revenue, cost, and profit per gateway (last 30 days)';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        $service = app(ProfitCalculationService::class);
        $breakdown = $service->getGatewayBreakdown(
            now()->subDays(30),
            now()
        );

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
                    ->state(function ($record) use ($breakdown) {
                        foreach ($breakdown as $item) {
                            if ($item['gateway'] === $record->name) {
                                return number_format($item['messages']);
                            }
                        }

                        return '0';
                    })
                    ->sortable(),
                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->state(function ($record) use ($breakdown) {
                        foreach ($breakdown as $item) {
                            if ($item['gateway'] === $record->name) {
                                return number_format($item['revenue'], 2).' BDT';
                            }
                        }

                        return '0.00 BDT';
                    }),
                TextColumn::make('cost')
                    ->label('Gateway Cost')
                    ->state(function ($record) use ($breakdown) {
                        foreach ($breakdown as $item) {
                            if ($item['gateway'] === $record->name) {
                                return number_format($item['cost'], 2).' BDT';
                            }
                        }

                        return '0.00 BDT';
                    }),
                TextColumn::make('profit')
                    ->label('Profit')
                    ->state(function ($record) use ($breakdown) {
                        foreach ($breakdown as $item) {
                            if ($item['gateway'] === $record->name) {
                                return number_format($item['profit'], 2).' BDT';
                            }
                        }

                        return '0.00 BDT';
                    }),
                TextColumn::make('margin')
                    ->label('Margin')
                    ->state(function ($record) use ($breakdown) {
                        foreach ($breakdown as $item) {
                            if ($item['gateway'] === $record->name) {
                                return $item['margin'].'%';
                            }
                        }

                        return '0%';
                    }),
            ]);
    }
}
