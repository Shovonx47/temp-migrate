<?php

namespace App\Filament\Sp\Pages;

use App\Enums\SpNavigationGroup;
use App\Filament\Sp\Actions\DistributeBalanceAction;
use App\Filament\Sp\Resources\Billing\Deposits\DepositResource;
use App\Models\SpDistribution;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class Wallet extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = SpNavigationGroup::Billing;

    protected static ?string $navigationLabel = 'Wallet';

    protected static ?string $title = 'Wallet';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.sp.wallet';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('topUp')
                ->label('Top Up')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('success')
                ->url(fn (): string => DepositResource::getUrl('index', panel: 'sp')),
            DistributeBalanceAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SpDistribution::query()
                    ->with('customer')
                    ->latest('created_at')
            )
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('—')
                    ->wrap()
                    ->limit(60),
                TextColumn::make('created_at')
                    ->label('Distributed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
