<?php

namespace App\Filament\Admin\Pages\Affiliates;

use App\Enums\SupermanNavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WithdrawalRequestsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Affiliates;

    protected static ?string $navigationLabel = 'Withdrawal Requests';

    protected static ?string $title = 'Withdrawal Requests';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'affiliates/withdrawals';

    protected string $view = 'filament.admin.blank';
}
