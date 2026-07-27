<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Operator;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class TopOperatorsWidget extends TableWidget
{
    protected static ?string $heading = 'Top Operators';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
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

        usort($stats, fn ($a, $b) => $b['messages'] <=> $a['messages']);

        $collection = collect(array_slice($stats, 0, 10));

        return $table
            ->query(
                Operator::query()
                    ->active()
                    ->with('country')
                    ->whereIn('name', $collection->pluck('operator'))
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Operator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country.name')
                    ->label('Country'),
                Tables\Columns\TextColumn::make('codes')
                    ->label('Prefixes')
                    ->formatStateUsing(fn ($state) => implode(', ', (array) $state)),
                Tables\Columns\TextColumn::make('masking_price')
                    ->label('Masking Rate')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' BDT'),
                Tables\Columns\TextColumn::make('non_masking_price')
                    ->label('Non-Masking Rate')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' BDT'),
            ]);
    }
}
