<?php

namespace App\Filament\Admin\Pages\SystemSettings;

use App\Enums\SupermanNavigationGroup;
use App\Models\RoutePlan;
use App\Models\RouteRule;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class RoutingSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemSettings;

    protected static ?string $navigationLabel = 'Routing Overview';

    protected static ?string $title = 'SMS Routing & Failover Overview';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'system-settings/routing';

    protected string $view = 'filament.admin.pages.system-settings.routing-overview-page';

    public function getViewData(): array
    {
        return [
            'totalPlans' => RoutePlan::count(),
            'activePlans' => RoutePlan::where('is_enabled', true)->count(),
            'totalRules' => RouteRule::count(),
            'activeRules' => RouteRule::where('is_enabled', true)->count(),
        ];
    }
}
