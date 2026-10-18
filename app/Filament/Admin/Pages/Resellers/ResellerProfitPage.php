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

class ResellerProfitPage extends BasePage
{
    use HasDateFilter;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = SupermanNavigationGroup::Resellers;

    protected static ?string $navigationLabel = 'S P Profit';

    protected static ?string $title = 'S P Profit';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'sps/profit';

    protected static string $routePath = 'sps/profit';

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
