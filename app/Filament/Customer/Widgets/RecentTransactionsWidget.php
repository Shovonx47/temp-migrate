<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Transactions';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::where('user_id', auth()->id())
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->toggleable(false),
                TextColumn::make('transactionable_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('direction')
                    ->badge(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable()
                    ->color(fn ($record) => $record->direction?->value === 'debit' ? 'danger' : 'success'),
                TextColumn::make('balance_after')
                    ->money('BDT')
                    ->label('Balance'),
            ])
            ->paginated(false);
    }
}
