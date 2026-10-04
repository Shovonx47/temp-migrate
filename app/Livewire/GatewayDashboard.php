<?php

namespace App\Livewire;

use App\Contracts\SupportsDashboard;
use App\Models\Gateway;
use App\Support\Facades\Sms;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class GatewayDashboard extends Component
{
    public int $gatewayId;

    public bool $loading = true;

    /** @var array<int, array{label: string, value: string, description: string|null, color: string|null, icon: string|null}> */
    public array $cards = [];

    public function mount(int $gatewayId): void
    {
        $this->gatewayId = $gatewayId;
    }

    public function loadBalances(): void
    {
        $cacheKey = "gateway:{$this->gatewayId}:dashboard";

        if (Cache::has($cacheKey)) {
            $this->cards = Cache::get($cacheKey);
        } else {
            $this->cards = $this->fetchBalances();
            Cache::put($cacheKey, $this->cards, 60);
        }

        $this->loading = false;
    }

    public function refreshBalances(): void
    {
        Cache::forget("gateway:{$this->gatewayId}:dashboard");

        $this->cards = $this->fetchBalances();
        $this->loading = false;
    }

    private function fetchBalances(): array
    {
        $gateway = Gateway::find($this->gatewayId);

        if (! $gateway) {
            return [];
        }

        $driver = Sms::usingGateway($gateway)->driver($gateway->driver->value);

        if (! $driver instanceof SupportsDashboard) {
            return [];
        }

        try {
            $dashboardData = $driver->getDashboardData();

            return array_map(fn ($card) => [
                'label' => $card->label,
                'value' => $card->value,
                'description' => $card->description,
                'color' => $card->color,
                'icon' => $card->icon,
            ], $dashboardData->cards);
        } catch (\Throwable) {
            return [];
        }
    }

    public function render()
    {
        return view('livewire.gateway-dashboard');
    }
}
