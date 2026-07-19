<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Messaging\Batches\BatchResource;
use App\Models\Batch;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?string $heading = 'Pending Approvals';

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Batch::withoutGlobalScopes()
                    ->with('user')
                    ->where('approval', 'pending')
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('body')
                    ->label('Message')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('id')
                    ->label('Action')
                    ->url(fn ($record): string => BatchResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
