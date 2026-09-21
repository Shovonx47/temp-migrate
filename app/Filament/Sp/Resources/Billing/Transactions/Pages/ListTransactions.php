<?php

namespace App\Filament\Sp\Resources\Billing\Transactions\Pages;

use App\Filament\Sp\Resources\Billing\Transactions\TransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;
}
