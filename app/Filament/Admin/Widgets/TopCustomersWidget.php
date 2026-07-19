<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopCustomersWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Customers';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::withoutGlobalScopes()
                    ->customers()
                    ->withSum('messages', 'cost')
                    ->withCount('messages')
                    ->orderByDesc('messages_sum_cost')
                    ->limit(5)
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
                    ->sortable()
                    ->color(fn (float $state): string => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('messages_sum_cost')
                    ->label('Total Spent')
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
