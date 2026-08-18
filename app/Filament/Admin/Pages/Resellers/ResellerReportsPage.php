<?php

namespace App\Filament\Admin\Pages\Resellers;

use App\Enums\SupermanNavigationGroup;
use App\Filament\Admin\Widgets\SpAdminFinancialOverviewWidget;
use App\Filament\Admin\Widgets\SpAdminTrafficTrendChart;
use App\Filament\Admin\Widgets\SpMarginBreakdownWidget;
use App\Filament\Admin\Widgets\SpMarginStatsWidget;
use App\Filament\Admin\Widgets\SpResellerPerformanceTableWidget;
use App\Filament\Concerns\HasDateFilter;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BasePage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
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

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make([
                'md' => 12,
            ])
                ->columnSpanFull()
                ->schema([
                    Select::make('sp_id')
                        ->label('Sales Partner')
                        ->placeholder('All Sales Partners')
                        ->options(fn () => User::withoutGlobalScopes()->sps()->whereNull('sp_id')->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->live()
                        ->columnSpan([
                            'md' => 4,
                            'xl' => 3,
                        ]),
                    Select::make('date_range')
                        ->options([
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'this_week' => 'This Week',
                            'last_7_days' => 'Last 7 Days',
                            'this_month' => 'This Month',
                            'last_30_days' => 'Last 30 Days',
                            'this_year' => 'This Year',
                            'custom' => 'Custom Range',
                        ])
                        ->default('last_30_days')
                        ->live()
                        ->selectablePlaceholder(false)
                        ->columnSpan([
                            'md' => 4,
                            'xl' => 3,
                        ]),
                    DatePicker::make('date_from')
                        ->label('From')
                        ->visible(fn (Get $get): bool => $get('date_range') === 'custom')
                        ->live(onBlur: true)
                        ->columnSpan([
                            'md' => 2,
                            'xl' => 3,
                        ]),
                    DatePicker::make('date_to')
                        ->label('To')
                        ->visible(fn (Get $get): bool => $get('date_range') === 'custom')
                        ->live(onBlur: true)
                        ->columnSpan([
                            'md' => 2,
                            'xl' => 3,
                        ]),
                ]),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            SpMarginStatsWidget::class,
            SpAdminFinancialOverviewWidget::class,
            SpMarginBreakdownWidget::class,
            SpAdminTrafficTrendChart::class,
            SpResellerPerformanceTableWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
