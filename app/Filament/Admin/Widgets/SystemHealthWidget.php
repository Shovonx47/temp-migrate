<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Health\SystemHealthChecker;
use Filament\Widgets\Widget;

class SystemHealthWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.system-health-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHealth(): array
    {
        return app(SystemHealthChecker::class)->runAllChecks();
    }
}
