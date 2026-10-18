<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Filament\Concerns\HasDateFilter;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class TopCustomersByPeriodWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top Customers by Period';

    protected ?string $pollingInterval = '300s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        return $table
            ->query(
                User::withoutGlobalScopes()
                    ->customers()
                    ->withSum(
                        ['messages' => fn ($q) => $q->where('created_at', '>=', $from)->where('created_at', '<=', $to)],
                        'cost'
                    )
                    ->withCount(
                        ['messages' => fn ($q) => $q->where('created_at', '>=', $from)->where('created_at', '<=', $to)]
                    )
                    ->orderByDesc('messages_sum_cost')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->url(fn (User $record): string => CustomerResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('balance')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('messages_sum_cost')
                    ->label('Period Spent')
                    ->money('BDT')
                    ->sortable()
                    ->color('danger'),
                TextColumn::make('messages_count')
                    ->label('Messages')
                    ->sortable(),
            ])
            ->defaultSort('messages_sum_cost', 'desc')
            ->paginated(false);
    }
}
