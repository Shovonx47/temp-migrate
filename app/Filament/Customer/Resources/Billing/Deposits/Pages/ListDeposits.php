<?php

namespace App\Filament\Customer\Resources\Billing\Deposits\Pages;

use App\Filament\Customer\Resources\Billing\Deposits\DepositResource;
use Filament\Resources\Pages\ListRecords;

class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;
}
