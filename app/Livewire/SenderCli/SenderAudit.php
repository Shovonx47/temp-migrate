<?php

namespace App\Livewire\SenderCli;

use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use Livewire\Component;
use Livewire\WithPagination;

class SenderAudit extends Component
{
    use WithPagination;

    public string $activeView = 'table'; // 'table' | 'activity'

    public string $search = '';

    public string $typeFilter = '';

    public string $gatewayFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingGatewayFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // 1. Overall Aggregated KPIs
        $totalSenders = Sender::count();
        $totalMessages = Message::whereNotNull('sender_id')->count();
        $deliveredMessages = Message::whereNotNull('sender_id')->where('status', 'delivered')->count();
        $totalRevenue = (float) Message::whereNotNull('sender_id')->where('status', '!=', 'failed')->sum('cost');

        $overallSuccessRate = $totalMessages > 0 ? round(($deliveredMessages / $totalMessages) * 100, 1) : 100;

        // 2. Query Senders with aggregated message stats
        $sendersQuery = Sender::query()
            ->with(['gateway', 'users'])
            ->withCount([
                'messages as total_messages',
                'messages as delivered_messages' => fn ($q) => $q->where('status', 'delivered'),
                'messages as failed_messages' => fn ($q) => $q->where('status', 'failed'),
            ])
            ->withSum('messages as total_spend', 'cost');

        if ($this->gatewayFilter !== '') {
            $sendersQuery->where('gateway_id', $this->gatewayFilter);
        }

        if ($this->typeFilter === 'masking') {
            $sendersQuery->whereIn('type', ['alphanumeric', 'shortcode']);
        } elseif ($this->typeFilter === 'non_masking') {
            $sendersQuery->where('type', 'numeric');
        }

        if ($this->search !== '') {
            $sendersQuery->where(function ($q) {
                $q->where('sender_id', 'like', "%{$this->search}%")
                    ->orWhereHas('gateway', fn ($gw) => $gw->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('users', fn ($u) => $u->where('name', 'like', "%{$this->search}%"));
            });
        }

        $senders = $sendersQuery->orderByDesc('total_messages')->paginate(15);

        // 3. Recent Message Audit Activity Stream
        $recentDispatchesQuery = Message::query()
            ->with(['sender', 'gateway', 'user'])
            ->whereNotNull('sender_id')
            ->orderByDesc('id')
            ->limit(50);

        if ($this->search !== '') {
            $recentDispatchesQuery->where(function ($q) {
                $q->where('to', 'like', "%{$this->search}%")
                    ->orWhereHas('sender', fn ($s) => $s->where('sender_id', 'like', "%{$this->search}%"));
            });
        }

        $recentDispatches = $recentDispatchesQuery->get();

        return view('livewire.sender-cli.sender-audit', [
            'senders' => $senders,
            'recentDispatches' => $recentDispatches,
            'gateways' => Gateway::all(),
            'totalSenders' => $totalSenders,
            'totalMessages' => $totalMessages,
            'deliveredMessages' => $deliveredMessages,
            'totalRevenue' => $totalRevenue,
            'overallSuccessRate' => $overallSuccessRate,
        ]);
    }
}
