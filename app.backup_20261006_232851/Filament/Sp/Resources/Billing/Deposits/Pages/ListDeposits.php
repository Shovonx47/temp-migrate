<?php

namespace App\Filament\Sp\Resources\Billing\Deposits\Pages;

use App\Filament\Sp\Resources\Billing\Deposits\Actions\DepositAction;
use App\Filament\Sp\Resources\Billing\Deposits\DepositResource;
use Filament\Resources\Pages\ListRecords;

class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DepositAction::make()
                ->label('Top Up'),
        ];
    }
}
