<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class UserGrowthChart extends ChartWidget
{
    protected ?string $heading = 'User Growth';

    protected ?string $description = 'New registrations per day (last 30 days)';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $data = User::withoutGlobalScopes()
            ->customers()
            ->where('created_at', '>=', now()->subDays(30))
            ->toBase()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->pluck('date')->map(fn ($date) => Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
