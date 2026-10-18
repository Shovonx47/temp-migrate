<?php

namespace App\Filament\Sp\Resources\Customers\Pages;

use App\Filament\Sp\Resources\Customers\Actions\DepositAction;
use App\Filament\Sp\Resources\Customers\CustomerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DepositAction::make(),
            EditAction::make()
                ->label('Edit Customer'),
        ];
    }
}
