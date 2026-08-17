<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use App\Enums\UserType;
use App\Models\Deposit;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ResellerPaymentGatewayPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'Payment Gateway';

    protected static ?string $title = 'S P Payment Gateway';

    protected static ?int $navigationSort = 17;

    protected static ?string $slug = 'sps/payment-gateway';

    protected string $view = 'filament.admin.blank';

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
                    ->label('Gateway Trx ID')
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->url(fn (Deposit $record): string => \App\Filament\Admin\Resources\Finance\Deposits\DepositResource::getUrl('view', [
                            'record' => $record,
                        ])),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
