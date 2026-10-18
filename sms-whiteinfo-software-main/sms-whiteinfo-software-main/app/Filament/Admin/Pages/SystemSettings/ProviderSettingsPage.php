<?php

namespace App\Filament\Admin\Pages\SystemSettings;

use App\Enums\SupermanNavigationGroup;
use App\Models\Gateway;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProviderSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServer;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::SystemSettings;

    protected static ?string $navigationLabel = 'Provider Overview';

    protected static ?string $title = 'Provider Settings Overview';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'system-settings/providers';

    protected string $view = 'filament.admin.pages.system-settings.provider-overview-page';

    public function getViewData(): array
    {
        return [
            'totalGateways' => Gateway::count(),
            'activeGateways' => Gateway::where('is_enabled', true)->count(),
            'healthyGateways' => Gateway::where('health_status', 'healthy')->count(),
            'otpGateways' => Gateway::where('is_otp_enabled', true)->count(),
        ];
    }
}
