<?php

namespace App\Filament\Admin\Resources\Finance\Deposits\Pages;

use App\Filament\Admin\Resources\Finance\Deposits\DepositResource;
use App\Filament\Exports\DepositExporter;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    public ?string $customFromDate = null;

    public ?string $customUntilDate = null;

    public bool $showCustomDate = false;

    public function setDatePreset(?string $preset): void
    {
        $this->tableFilters['date_preset']['value'] = $preset;

        if ($preset) {
            $this->tableFilters['date']['from'] = null;
            $this->tableFilters['date']['until'] = null;
            $this->customFromDate = null;
            $this->customUntilDate = null;
            $this->showCustomDate = false;
        }

    }

    public function clearDatePreset(): void
    {
        $this->tableFilters['date_preset']['value'] = null;
    }

    public function openCustomDate(): void
    {
        $this->showCustomDate = true;

        $this->customFromDate = $this->tableFilters['date']['from'] ?? null;
        $this->customUntilDate = $this->tableFilters['date']['until'] ?? null;

        $this->tableFilters['date_preset']['value'] = null;
    }

    public function applyCustomDate(): void
    {
        // Custom date overrides every quick preset.
        $this->tableFilters['date_preset']['value'] = null;

        // Apply the selected range to Filament's actual table filter.
        $this->tableFilters['date']['from'] = $this->customFromDate ?: null;
        $this->tableFilters['date']['until'] = $this->customUntilDate ?: null;

        // Close the custom date panel after applying.
        $this->showCustomDate = false;
    }

    public function cancelCustomDate(): void
    {
        $this->showCustomDate = false;
    }

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->exporter(DepositExporter::class)
                ->columnMappingColumns(3)
                ->enableVisibleTableColumnsByDefault(),
        ];
    }
}
