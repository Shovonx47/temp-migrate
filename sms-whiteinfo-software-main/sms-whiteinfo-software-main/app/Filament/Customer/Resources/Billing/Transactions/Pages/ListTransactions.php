<?php

namespace App\Filament\Customer\Resources\Billing\Transactions\Pages;

use App\Filament\Customer\Resources\Billing\Transactions\TransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}
