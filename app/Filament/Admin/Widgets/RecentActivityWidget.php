<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Message;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Activity';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Message::withoutGlobalScopes()
                    ->with('user')
                    ->latest('sent_at')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->toggleable(false),

                TextColumn::make('to')
                    ->label('To')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('source')
                    ->badge(),

                TextColumn::make('cost')
                    ->money('BDT')
                    ->sortable(),

                TextColumn::make('sent_at')
                    ->label('Sent At')
                    ->sortable()
                    ->dateTime('M d, Y H:i'),
            ])
            ->defaultSort('sent_at', 'desc')
            ->paginated(false);
    }
}
