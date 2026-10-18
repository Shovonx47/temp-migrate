<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use App\Enums\UserType;
use App\Models\Transaction;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ResellerStatementsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'S P Statements';

    protected static ?string $title = 'S P Statements';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'sps/statements';

    protected string $view = 'filament.sp.wallet';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->whereHas('user', fn ($query) => $query->where('type', UserType::Sp))
                    ->with('user')
                    ->latest()
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('S P')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('direction')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('balance_after')
                    ->label('Balance')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('S P')
                    ->relationship('user', 'name', fn ($query) => $query->where('type', UserType::Sp)),
                SelectFilter::make('direction')
                    ->options([
                        'credit' => 'Credit',
                        'debit' => 'Debit',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
