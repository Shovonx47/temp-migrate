<?php

namespace App\Filament\Admin\Resources\Billing\TariffPlans\RelationManagers;

use App\Models\GatewayOperatorRate;
use App\Models\Operator;
use App\Models\TariffRate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TariffRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'tariffRates';

    protected static ?string $title = 'Rate List';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('operator_id')
                ->label('Operator')
                ->relationship('operator', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->distinct()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
            TextInput::make('masking_price')
                ->label('Masking price (per part)')
                ->prefix('৳')
                ->prefixIcon(Heroicon::Banknotes)
                ->numeric()
                ->step('0.0001')
                ->minValue(0)
                ->nullable(),
            TextInput::make('non_masking_price')
                ->label('Non-masking price (per part)')
                ->prefix('৳')
                ->prefixIcon(Heroicon::Banknotes)
                ->numeric()
                ->step('0.0001')
                ->minValue(0)
                ->nullable(),
            DatePicker::make('effective_from')
                ->label('Effective from')
                ->nullable()
                ->helperText('Leave blank to apply immediately.'),
            Textarea::make('notes')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('operator.name')
            ->columns([
                TextColumn::make('operator.country.name')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('operator.name')
                    ->label('Operator')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('masking_price')
                    ->label('Masking (per part)')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—')
                    ->color(fn (TariffRate $record): ?string => $this->isBelowGatewayCost($record, true) ? 'danger' : null),
                TextColumn::make('non_masking_price')
                    ->label('Non-masking (per part)')
                    ->prefix('৳')
                    ->numeric(4)
                    ->placeholder('—')
                    ->color(fn (TariffRate $record): ?string => $this->isBelowGatewayCost($record, false) ? 'danger' : null),
                TextColumn::make('effective_from')
                    ->label('Effective From')
                    ->date()
                    ->placeholder('Immediate'),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Rate'),
                Action::make('import_csv')
                    ->label('Import CSV')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('gray')
                    ->form([
                        FileUpload::make('csv_file')
                            ->label('CSV File')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                            ->required()
                            ->disk('local')
                            ->directory('tariff-imports')
                            ->helperText('Columns: operator_name, masking_price, non_masking_price, effective_from (optional), notes (optional). First row is the header.'),
                    ])
                    ->action(function (array $data): void {
                        $this->importFromCsv($data['csv_file']);
                    }),
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(function (): StreamedResponse {
                        return $this->exportToCsv();
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->defaultSort('operator.name');
    }

    /**
     * Check if the sell price is below the gateway cost for this operator,
     * to visually flag potential margin issues.
     */
    private function isBelowGatewayCost(TariffRate $rate, bool $isMasking): bool
    {
        $field = $isMasking ? 'masking_price' : 'non_masking_price';
        $sellPrice = $rate->{$field};

        if ($sellPrice === null) {
            return false;
        }

        // Get the lowest gateway cost for this operator across all gateways
        $gatewayCost = GatewayOperatorRate::where('operator_id', $rate->operator_id)
            ->whereNotNull($field)
            ->min($field);

        return $gatewayCost !== null && $sellPrice < $gatewayCost;
    }

    private function importFromCsv(string $filePath): void
    {
        $planId = $this->getOwnerRecord()->id;
        $path = storage_path('app/'.$filePath);

        if (! file_exists($path)) {
            Notification::make()->danger()->title('Import failed: file not found.')->send();

            return;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle); // skip header row

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            [$operatorName, $maskingPrice, $nonMaskingPrice, $effectiveFrom, $notes] = array_pad($row, 5, null);

            $operator = Operator::whereRaw('LOWER(name) = ?', [strtolower(trim($operatorName))])->first();

            if (! $operator) {
                $errors[] = "Operator not found: {$operatorName}";
                $skipped++;

                continue;
            }

            TariffRate::updateOrCreate(
                ['tariff_plan_id' => $planId, 'operator_id' => $operator->id],
                [
                    'masking_price' => filled($maskingPrice) ? (float) $maskingPrice : null,
                    'non_masking_price' => filled($nonMaskingPrice) ? (float) $nonMaskingPrice : null,
                    'effective_from' => filled($effectiveFrom) ? $effectiveFrom : null,
                    'notes' => filled($notes) ? trim($notes) : null,
                ],
            );
            $imported++;
        }

        fclose($handle);

        $message = "Imported {$imported} rows.";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} (unrecognised operators: ".implode(', ', array_slice($errors, 0, 5)).').';
        }

        Notification::make()
            ->title($skipped > 0 ? 'Import completed with warnings' : 'Import successful')
            ->body($message)
            ->when($skipped === 0, fn ($n) => $n->success())
            ->when($skipped > 0, fn ($n) => $n->warning())
            ->send();
    }

    private function exportToCsv(): StreamedResponse
    {
        $plan = $this->getOwnerRecord();
        $rates = TariffRate::with('operator.country')
            ->where('tariff_plan_id', $plan->id)
            ->orderBy('operator_id')
            ->get();

        $filename = Str::slug($plan->name).'-rates-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rates): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['operator_name', 'country', 'masking_price', 'non_masking_price', 'effective_from', 'notes']);

            foreach ($rates as $rate) {
                fputcsv($handle, [
                    $rate->operator?->name ?? '',
                    $rate->operator?->country?->name ?? '',
                    $rate->masking_price ?? '',
                    $rate->non_masking_price ?? '',
                    $rate->effective_from?->toDateString() ?? '',
                    $rate->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
