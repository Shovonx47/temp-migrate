<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Widgets\SpMarginBreakdownWidget;
use App\Filament\Admin\Widgets\SpMarginStatsWidget;
use App\Filament\Concerns\HasDateFilter;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ResellerReportsPage extends BasePage
{
    use HasDateFilter;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'S P Reports';

    protected static ?string $title = 'S P Reports';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'sps/reports';

    protected static string $routePath = 'sps/reports';

    public function getWidgets(): array
    {
        return [
            SpMarginStatsWidget::class,
            SpMarginBreakdownWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
