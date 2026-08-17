<?php

namespace App\Filament\Sp\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Filament\Sp\Resources\Customers\CustomerResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class SpCustomerPerformanceTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Customer Activity in Period';

    protected ?string $pollingInterval = '300s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        /** @var User $actingUser */
        $actingUser = Filament::auth()->user();
        $sp = ($actingUser->type->isSp() && $actingUser->sp_id) ? (User::find($actingUser->sp_id) ?? $actingUser) : $actingUser;

        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        return $table
            ->query(
                User::query()
                    ->where('sp_id', $sp->id)
                    ->where('type', \App\Enums\UserType::Customer)
                    ->withSum(
                        ['messages' => fn ($q) => $q->where('created_at', '>=', $from)->where('created_at', '<=', $to)->where('status', '!=', 'failed')],
                        'cost'
                    )
                    ->withCount(
                        ['messages' => fn ($q) => $q->where('created_at', '>=', $from)->where('created_at', '<=', $to)]
                    )
                    ->withSum(
                        ['receivedDistributions' => fn ($q) => $q->where('created_at', '>=', $from)->where('created_at', '<=', $to)],
                        'amount'
                    )
                    ->orderByDesc('messages_sum_cost')
            )

            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->url(fn (User $record): string => CustomerResource::getUrl('view', ['record' => $record])),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->formatStateUsing(fn ($state) => (string) $state)
                    ->searchable(),

                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('balance')
                    ->label('Current Balance')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('messages_sum_cost')
                    ->label('Period Spend')
                    ->money('BDT')
                    ->placeholder('0.00 BDT')
                    ->sortable()
                    ->color('success'),
                TextColumn::make('messages_count')
                    ->label('Messages Sent')
                    ->sortable(),
                TextColumn::make('received_distributions_sum_amount')
                    ->label('Balance Received')
                    ->money('BDT')
                    ->placeholder('0.00 BDT')
                    ->sortable()
                    ->color('primary'),
            ])
            ->defaultSort('messages_sum_cost', 'desc')
            ->paginated([10, 25]);
    }
}
