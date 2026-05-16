<?php

namespace App\Filament\Admin\Resources\Users\Customers\Pages;

use App\Filament\Admin\Resources\Users\Customers\Actions\ApprovalAction;
use App\Filament\Admin\Resources\Users\Customers\Actions\DepositAction;
use App\Filament\Admin\Resources\Users\Customers\CustomerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ApprovalAction::make(),
            DepositAction::make(),
            EditAction::make()
                ->label('Edit Customer'),
        ];
    }
}
