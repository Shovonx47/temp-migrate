<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use App\Enums\UserType;
use App\Models\Deposit;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ResellerBillingPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'S P Billing';

    protected static ?string $title = 'S P Billing';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'sps/billing';

    protected string $view = 'filament.sp.wallet';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Deposit::query()
                    ->whereHas('user', fn ($query) => $query->where('type', UserType::Sp))
                    ->with('user')
                    ->latest()
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('S P')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->badge()
                    ->sortable(),
                TextColumn::make('transaction_id')
                    ->label('Trx ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('S P')
                    ->relationship('user', 'name', fn ($query) => $query->where('type', UserType::Sp)),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
