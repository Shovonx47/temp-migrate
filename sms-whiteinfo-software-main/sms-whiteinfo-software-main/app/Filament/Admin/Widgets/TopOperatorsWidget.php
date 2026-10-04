<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Operator;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopOperatorsWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Operators';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->getOperatorStats())
            ->paginated(false)
            ->columns([
                TextColumn::make('operator')
                    ->label('Operator')
                    ->weight('medium'),

                TextColumn::make('messages')
                    ->label('Messages')
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->money('BDT')
                    ->alignEnd(),
            ]);
    }

    /**
     * Aggregated message traffic + revenue per operator, matched by MSISDN prefix.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getOperatorStats(): array
    {
        $operators = Operator::query()->active()->with('country')->get();

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
            ->where('created_at', '>=', now()->subDays(30))
            ->whereNotNull('to')
            ->select('to', 'cost')
            ->get();

        $stats = [];
        foreach ($messages as $message) {
            $digits = ltrim($message->to, '+');
            $matched = 'Unknown';

            foreach ($codeMap as $prefix => $name) {
                if (str_starts_with($digits, $prefix)) {
                    $matched = $name;
                    break;
                }
            }

            if (! isset($stats[$matched])) {
                $stats[$matched] = [
                    'operator' => $matched,
                    'messages' => 0,
                    'revenue' => 0.0,
                ];
            }

            $stats[$matched]['messages']++;
            $stats[$matched]['revenue'] += (float) $message->cost;
        }

        usort($stats, fn (array $a, array $b): int => $b['messages'] <=> $a['messages']);

        return array_slice(array_values($stats), 0, 10);
    }
}
