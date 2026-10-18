<?php

namespace App\Filament\Admin\Resources\Finance\Deposits\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepositForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.name')
                    ->label('Customer')
                    ->disabled(),

                TextInput::make('amount')
                    ->label('Amount')
                    ->disabled(),

                TextInput::make('currency')
                    ->label('Currency')
                    ->disabled(),

                TextInput::make('payment_method')
                    ->label('Payment Method')
                    ->disabled(),

                TextInput::make('transaction_id')
                    ->label('PipaPay ID')
                    ->disabled(),

                TextInput::make('status')
                    ->label('Status')
                    ->disabled(),

                Placeholder::make('paid_at')
                    ->label('Paid At')
                    ->content(fn ($record) => $record?->paid_at?->format('Y-m-d H:i:s') ?? 'N/A'),

                Placeholder::make('meta')
                    ->label('Gateway Response')
                    ->content(fn ($record) => $record?->meta ? json_encode($record->meta, JSON_PRETTY_PRINT) : 'N/A'),
            ]);
    }
}
