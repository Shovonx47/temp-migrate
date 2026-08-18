<?php

namespace App\Livewire\SenderCli;

use App\Enums\SenderType;
use App\Models\Gateway;
use App\Models\GatewayOperatorRate;
use App\Models\Operator;
use App\Models\Sender;
use Livewire\Component;
use Livewire\WithPagination;

class MaskingList extends Component
{
    use WithPagination;

    public string $activeTab = 'rates'; // 'rates' | 'senders'

    public string $search = '';

    public string $gatewayFilter = '';

    public string $operatorFilter = '';

    // Modal state for editing/adding masking rate
    public bool $showRateModal = false;

    public ?int $editingRateId = null;

    public ?int $selectedGatewayId = null;

    public ?int $selectedOperatorId = null;

    public ?float $maskingPrice = null;

    public ?float $nonMaskingPrice = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGatewayFilter(): void
    {
        $this->resetPage();
    }

    public function updatingOperatorFilter(): void
    {
        $this->resetPage();
    }

    public function openEditRateModal(int $rateId): void
    {
        $rate = GatewayOperatorRate::findOrFail($rateId);
        $this->editingRateId = $rate->id;
        $this->selectedGatewayId = $rate->gateway_id;
        $this->selectedOperatorId = $rate->operator_id;
        $this->maskingPrice = $rate->masking_price;
        $this->nonMaskingPrice = $rate->non_masking_price;
        $this->showRateModal = true;
    }

    public function openNewRateModal(): void
    {
        $this->editingRateId = null;
        $this->selectedGatewayId = Gateway::first()?->id;
        $this->selectedOperatorId = Operator::first()?->id;
        $this->maskingPrice = null;
        $this->nonMaskingPrice = null;
        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $this->validate([
            'selectedGatewayId' => 'required|exists:gateways,id',
            'selectedOperatorId' => 'required|exists:operators,id',
            'maskingPrice' => 'nullable|numeric|min:0',
        ]);

        GatewayOperatorRate::updateOrCreate(
            [
                'gateway_id' => $this->selectedGatewayId,
                'operator_id' => $this->selectedOperatorId,
            ],
            [
                'masking_price' => $this->maskingPrice,
            ]
        );

        $this->showRateModal = false;
        $this->editingRateId = null;
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Masking rate saved successfully!']);
    }

    public function render()
    {
        // 1. Stats calculation
        $totalMaskingSenders = Sender::query()
            ->whereIn('type', [SenderType::AlphaNumeric->value, SenderType::Shortcode->value, 'alphanumeric', 'shortcode'])
            ->count();

        $ratesQuery = GatewayOperatorRate::query()
            ->with(['gateway', 'operator.country'])
            ->whereNotNull('masking_price')
            ->where('masking_price', '>', 0);

        if ($this->gatewayFilter !== '') {
            $ratesQuery->where('gateway_id', $this->gatewayFilter);
        }

        if ($this->operatorFilter !== '') {
            $ratesQuery->where('operator_id', $this->operatorFilter);
        }

        if ($this->search !== '') {
            $ratesQuery->where(function ($q) {
                $q->whereHas('gateway', fn ($gw) => $gw->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('operator', fn ($op) => $op->where('name', 'like', "%{$this->search}%"));
            });
        }

        $allRates = (clone $ratesQuery)->get();
        $avgMaskingPrice = $allRates->avg('masking_price') ?? 0;
        $configuredOperatorsCount = $allRates->pluck('operator_id')->unique()->count();
        $activeGatewaysCount = $allRates->pluck('gateway_id')->unique()->count();

        $rates = $ratesQuery->orderBy('gateway_id')->paginate(15);

        // 2. Senders Query
        $sendersQuery = Sender::query()
            ->with(['gateway', 'users'])
            ->withCount('messages')
            ->whereIn('type', [SenderType::AlphaNumeric->value, SenderType::Shortcode->value, 'alphanumeric', 'shortcode']);

        if ($this->gatewayFilter !== '') {
            $sendersQuery->where('gateway_id', $this->gatewayFilter);
        }

        if ($this->search !== '') {
            $sendersQuery->where(function ($q) {
                $q->where('sender_id', 'like', "%{$this->search}%")
                    ->orWhereHas('gateway', fn ($gw) => $gw->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('users', fn ($u) => $u->where('name', 'like', "%{$this->search}%"));
            });
        }

        $senders = $sendersQuery->orderByDesc('id')->paginate(15, ['*'], 'senders_page');

        return view('livewire.sender-cli.masking-list', [
            'rates' => $rates,
            'senders' => $senders,
            'gateways' => Gateway::orderBy('name')->get(),
            'operators' => Operator::orderBy('name')->get(),
            'totalMaskingSenders' => $totalMaskingSenders,
            'configuredOperatorsCount' => $configuredOperatorsCount,
            'activeGatewaysCount' => $activeGatewaysCount,
            'avgMaskingPrice' => $avgMaskingPrice,
        ]);
    }
}
