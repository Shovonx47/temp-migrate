<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\Pages;

use App\Contracts\SupportsDashboard;
use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use App\Support\Facades\Sms;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
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

        $components[] = $this->getInfolistContentComponent();
        $components[] = $this->getRelationManagersContentComponent();

        return $schema->components($components);
    }

    private function buildDashboardSection(): ?Section
    {
        $gateway = $this->getRecord();
        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $driver instanceof SupportsDashboard) {
            return null;
        }

        try {
            $dashboardData = $driver->getDashboardData();
        } catch (\Throwable) {
            return null;
        }

        if (empty($dashboardData->cards)) {
            return null;
        }

        return Section::make('Gateway Dashboard')
            ->schema([
                View::make('filament.admin.gateway-dashboard')
                    ->columnSpanFull()
                    ->viewData(['cards' => $dashboardData->cards]),
            ]);
    }
}
