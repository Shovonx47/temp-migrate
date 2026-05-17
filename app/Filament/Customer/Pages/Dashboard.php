<?php

namespace App\Filament\Customer\Pages;

use App\Filament\Customer\Resources\Billing\Deposits\Actions\DepositAction;
use Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    protected function getHeaderActions(): array
    {
        return [
            DepositAction::make(),
        ];
    }
}
