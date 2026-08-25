<?php

namespace App\Filament\Customer\Pages;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Concerns\HasDateFilter;
use App\Filament\Customer\Widgets\CampaignPerformanceChart;
use App\Filament\Customer\Widgets\CampaignStatsWidget;
use App\Filament\Customer\Widgets\DeliveryRateTrendChart;
use App\Filament\Customer\Widgets\MessageStatsByPeriodWidget;
use App\Filament\Customer\Widgets\MessageTrendChart;
use BackedEnum;
use Filament\Pages\Dashboard as BasePage;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Carbon;

class MessagingAnalytics extends BasePage
{
    use HasDateFilter;

    protected static string $routePath = 'analytics/messaging';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = CustomerNavigationGroup::Analytics;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Messaging Analytics';

    protected static ?string $navigationLabel = 'Messaging';

    public function getHeaderActions(): array
    {
        return [
            Action::make('exportAs')
                ->label('Export As')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->modalHeading('Export Messaging Analytics')
                ->modalDescription('Select your preferred report format to download.')
                ->modalSubmitActionLabel('Download Report')
                ->schema([
                    \Filament\Forms\Components\Radio::make('format')
                        ->label('Select Format')
                        ->options([
                            'excel' => 'Excel / CSV Spreadsheet (.csv)',
                            'pdf' => 'PDF Document (.pdf)',
                        ])
                        ->default('excel')
                        ->required(),
                ])
                ->action(function (array $data) {
                    return ($data['format'] ?? 'excel') === 'pdf'
                        ? $this->exportPdf()
                        : $this->exportExcel();
                }),
        ];
    }

    public function exportExcel()
    {
        $user = auth()->user();
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $dailyStats = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('DATE(created_at) as date_val')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as cost")
            ->groupBy('date_val')
            ->orderBy('date_val', 'desc')
            ->get();

        $headers = [
            'Date',
            'Total Messages',
            'Successful',
            'Delivered',
            'Failed',
            'Success Rate',
            'Spend (BDT)',
        ];

        $rows = $dailyStats->map(function ($row) {
            $total = (int) $row->total;
            $success = (int) $row->successful;
            $rate = $total > 0 ? round(($success / $total) * 100, 1).'%' : '0%';

            return [
                Carbon::parse($row->date_val)->format('Y-m-d'),
                $total,
                $success,
                (int) $row->delivered,
                (int) $row->failed,
                $rate,
                number_format((float) $row->cost, 2),
            ];
        });

        $filename = 'messaging-analytics-'.now()->format('Y-m-d-His').'.csv';

        return ReportExportService::downloadCsv($filename, $headers, $rows);
    }

    public function exportPdf()
    {
        $user = auth()->user();
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $statsRaw = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as total_cost")
            ->selectRaw('COALESCE(SUM(parts), 0) as total_parts')
            ->selectRaw('COALESCE(AVG(parts), 0) as avg_parts')
            ->selectRaw('COALESCE(SUM(is_unicode), 0) as unicode')
            ->first();

        $total = (int) ($statsRaw->total ?? 0);
        $successful = (int) ($statsRaw->successful ?? 0);
        $delivered = (int) ($statsRaw->delivered ?? 0);
        $failed = (int) ($statsRaw->failed ?? 0);
        $totalCost = (float) ($statsRaw->total_cost ?? 0);
        $totalParts = (int) ($statsRaw->total_parts ?? 0);
        $avgParts = (float) ($statsRaw->avg_parts ?? 0);
        $unicode = (int) ($statsRaw->unicode ?? 0);

        $successRate = $total > 0 ? round(($successful / $total) * 100, 1).'%' : '0%';

        $dailyStats = $user->messages()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('DATE(created_at) as date_val')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('sent', 'delivered') THEN 1 ELSE 0 END), 0) as successful")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('failed', 'dlr_failed') THEN 1 ELSE 0 END), 0) as failed")
            ->selectRaw("COALESCE(SUM(CASE WHEN status NOT IN ('failed', 'dlr_failed') THEN cost ELSE 0 END), 0) as cost")
            ->groupBy('date_val')
            ->orderBy('date_val', 'desc')
            ->get()
            ->map(fn ($r) => [
                'date' => Carbon::parse($r->date_val)->format('d M Y'),
                'total' => (int) $r->total,
                'successful' => (int) $r->successful,
                'delivered' => (int) $r->delivered,
                'failed' => (int) $r->failed,
                'success_rate' => (int) $r->total > 0 ? round(((int) $r->successful / (int) $r->total) * 100, 1).'%' : '0%',
                'cost' => (float) $r->cost,
            ])
            ->toArray();

        $data = [
            'user' => $user,
            'period' => $from->format('d M Y').' - '.$to->format('d M Y'),
            'stats' => [
                'total' => $total,
                'successful' => $successful,
                'delivered' => $delivered,
                'failed' => $failed,
                'success_rate' => $successRate,
                'total_cost' => $totalCost,
                'total_parts' => $totalParts,
                'avg_parts' => $avgParts,
                'unicode' => $unicode,
            ],
            'dailyStats' => $dailyStats,
        ];

        $filename = 'messaging-analytics-'.now()->format('Y-m-d-His').'.pdf';

        return ReportExportService::downloadPdf($filename, 'reports.pdf.messaging-analytics', $data);
    }

    public function getWidgets(): array
    {
        return [
            MessageStatsByPeriodWidget::class,
            CampaignStatsWidget::class,
            MessageTrendChart::class,
            DeliveryRateTrendChart::class,
            CampaignPerformanceChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
