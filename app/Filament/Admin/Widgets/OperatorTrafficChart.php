<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Concerns\HasDateFilter;
use App\Models\Operator;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class OperatorTrafficChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Operator Traffic';

    protected ?string $description = 'Messages per operator';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        [$from, $to] = HasDateFilter::resolveDateRange($this->pageFilters);

        $operators = Operator::query()
            ->active()
            ->with('country')
            ->get();

        $codeMap = [];
        foreach ($operators as $operator) {
            if (! $operator->country) {
                continue;
            }
            $countryCode = (string) $operator->country->code;
            foreach ((array) $operator->codes as $code) {
                $codeMap[$countryCode.(string) $code] = $operator->name;
            }
        }

        $messages = DB::table('messages')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->whereNotNull('to')
            ->select('to')
            ->get();

        $counts = [];
        foreach ($messages as $message) {
            $digits = ltrim($message->to, '+');
            $matched = 'Unknown';

            foreach ($codeMap as $prefix => $name) {
                if (str_starts_with($digits, $prefix)) {
                    $matched = $name;
                    break;
                }
            }

            $counts[$matched] = ($counts[$matched] ?? 0) + 1;
        }

        arsort($counts);

        $colors = ['#10b981', '#0ea5e9', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'];

        return [
            'datasets' => [
                [
                    'data' => array_values($counts),
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
