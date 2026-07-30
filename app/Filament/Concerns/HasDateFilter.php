<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

trait HasDateFilter
{
    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
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
                    ->columnSpan(1),
                DatePicker::make('date_from')
                    ->label('From')
                    ->visible(fn (Get $get): bool => $get('date_range') === 'custom')
                    ->columnSpan(1),
                DatePicker::make('date_to')
                    ->label('To')
                    ->visible(fn (Get $get): bool => $get('date_range') === 'custom')
                    ->columnSpan(1),
            ]),
        ]);
    }

    /**
     * Resolve the date range from filter values.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function resolveDateRange(?array $filters): array
    {
        $range = $filters['date_range'] ?? 'last_30_days';

        return match ($range) {
            'today' => [today(), now()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()],
            'last_7_days' => [now()->subDays(7), now()],
            'this_month' => [now()->startOfMonth(), now()],
            'last_30_days' => [now()->subDays(30), now()],
            'this_year' => [now()->startOfYear(), now()],
            'custom' => [
                Carbon::parse($filters['date_from'] ?? now()->subDays(30))->startOfDay(),
                Carbon::parse($filters['date_to'] ?? now())->endOfDay(),
            ],
            default => [now()->subDays(30), now()],
        };
    }
}
