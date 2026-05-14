<?php

namespace App\Filament\Admin\Resources\Deposits;

use App\Filament\Admin\Resources\Deposits\Pages\EditDeposit;
use App\Filament\Admin\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Admin\Resources\Deposits\Pages\ViewDeposit;
use App\Filament\Admin\Resources\Deposits\Schemas\DepositForm;
use App\Filament\Admin\Resources\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|null|\UnitEnum $navigationGroup = 'Payments';

    protected static ?string $navigationLabel = 'Deposits';

    public static function form(Schema $schema): Schema
    {
        return DepositForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->description('Customer who made this deposit')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Name')
                                    ->weight('bold'),
                                TextEntry::make('user.email')
                                    ->label('Email')
                                    ->copyable(),
                                TextEntry::make('user.phone')
                                    ->label('Phone'),
                            ]),
                    ]),

                Section::make('Payment')
                    ->description('Payment details and status')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('Amount')
                                    ->money('BDT')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary'),
                                TextEntry::make('currency')
                                    ->label('Currency'),
                                TextEntry::make('payment_method')
                                    ->label('Method')
                                    ->badge(),
                                TextEntry::make('transaction_id')
                                    ->label('PipaPay ID')
                                    ->copyable(),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(),
                                TextEntry::make('paid_at')
                                    ->label('Paid At')
                                    ->dateTime()
                                    ->placeholder('Not paid yet'),
                            ]),
                    ]),

                Section::make('Gateway Response')
                    ->description('Raw payload from PipaPay')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        KeyValueEntry::make('meta')
                            ->label('Response Data'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeposits::route('/'),
            'view' => ViewDeposit::route('/{record}/view'),
            'edit' => EditDeposit::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
