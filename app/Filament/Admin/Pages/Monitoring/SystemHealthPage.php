<?php

namespace App\Filament\Admin\Pages\Monitoring;

use App\Enums\SupermanNavigationGroup;
use App\Services\Health\SystemHealthChecker;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SystemHealthPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Monitoring;

    protected static ?string $navigationLabel = 'System Health';

    protected static ?string $title = 'System Health';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'monitoring/system-health';

    protected string $view = 'filament.admin.pages.monitoring.system-health-page';

    public array $health = [];

    public function mount(SystemHealthChecker $checker): void
    {
        $this->health = $checker->runAllChecks();
    }

    public function refreshHealth(SystemHealthChecker $checker): void
    {
        $this->health = $checker->runAllChecks();

        Notification::make()
            ->title('Health checks refreshed')
            ->success()
            ->duration(2000)
            ->send();
    }

    public function pollHealth(SystemHealthChecker $checker): void
    {
        $this->health = $checker->runAllChecks();
    }
}
