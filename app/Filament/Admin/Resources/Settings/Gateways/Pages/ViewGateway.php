<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Pages;

use App\Contracts\SupportsDashboard;
use App\Enums\ProfitStatus;
use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use App\Services\Billing\GatewayProfitCalculationService;
use App\Support\Facades\Sms;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewGateway extends ViewRecord
{
    protected static string $resource = GatewayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        $components = [];

        $dashboardSection = $this->buildDashboardSection();
        if ($dashboardSection !== null) {
            $components[] = $dashboardSection;
        }

        $components[] = $this->buildBillingSummarySection();
        $components[] = $this->getInfolistContentComponent();
        $components[] = $this->getRelationManagersContentComponent();

        return $schema->components($components);
    }

    private function buildBillingSummarySection(): Section
    {
        $gateway = $this->getRecord();
        $profitService = app(GatewayProfitCalculationService::class);
        $result = $profitService->calculateForGateway($gateway, now()->startOfMonth(), now()->endOfMonth());

        return Section::make('Billing & Profit Summary (Current Month)')
            ->description('Authoritative cost resolution and tariff-based revenue calculation')
            ->schema([
                Grid::make(4)->schema([
                    TextEntry::make('current_revenue')
                        ->label('Revenue (Tariff)')
                        ->state(fn () => number_format($result->revenue->total, 2).' ৳')
                        ->badge()
                        ->color('success'),
                    TextEntry::make('current_operator_cost')
                        ->label('Operator Cost')
                        ->state(fn () => number_format($result->cost->operatorCost, 2).' ৳')
                        ->badge()
                        ->color('warning'),
                    TextEntry::make('current_dipping_cost')
                        ->label('Dipping Cost')
                        ->state(fn () => number_format($result->cost->primaryDippingCost, 2).' ৳')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('current_total_cost')
                        ->label('Total Cost')
                        ->state(fn () => number_format($result->cost->totalCost, 2).' ৳')
                        ->weight('bold'),
                ]),
                Grid::make(4)->schema([
                    TextEntry::make('current_profit')
                        ->label('Net Profit')
                        ->state(fn () => $result->status === ProfitStatus::Incomplete
                            ? ($result->incompleteReason ?? 'Incomplete')
                            : number_format((float) $result->profit, 2).' ৳')
                        ->badge(fn () => $result->status === ProfitStatus::Incomplete)
                        ->color(fn () => $result->status === ProfitStatus::Incomplete ? 'danger' : ((float) $result->profit >= 0 ? 'success' : 'danger')),
                    TextEntry::make('current_margin')
                        ->label('Profit Margin')
                        ->state(fn () => $result->status === ProfitStatus::Incomplete ? 'N/A' : $result->margin.'%'),
                    TextEntry::make('current_dlr_rate')
                        ->label('DLR Delivery %')
                        ->state(fn () => ! $gateway->dlr_support ? 'N/A (Disabled)' : ($result->cost->dlrMetrics?->deliveryPercent !== null ? $result->cost->dlrMetrics->deliveryPercent.'%' : '—'))
                        ->badge(fn () => ! $gateway->dlr_support)
                        ->color(fn () => ! $gateway->dlr_support ? 'gray' : 'primary'),
                    TextEntry::make('current_message_count')
                        ->label('Total Messages')
                        ->state(fn () => number_format($result->revenue->messageCount)),
                ]),
            ]);
    }

    private function buildDashboardSection(): ?Section
    {
        $gateway = $this->getRecord();
        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $driver instanceof SupportsDashboard) {
            return null;
        }

        return Section::make('Gateway Dashboard')
            ->schema([
                Livewire::make('gateway-dashboard', ['gatewayId' => $gateway->id])
                    ->columnSpanFull(),
            ]);
    }
}
