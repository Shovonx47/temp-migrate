<?php

namespace App\Livewire\SenderCli;

use App\Enums\SenderType;
use App\Models\Gateway;
use App\Models\Sender;
use Livewire\Component;
use Livewire\WithPagination;

class MaskingList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $gatewayFilter = '';

    public string $assignmentFilter = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'gatewayFilter', 'assignmentFilter'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $senders = Sender::query()
            ->with(['gateway:id,name', 'users:id,name'])
            ->withCount(['users', 'messages'])
            ->whereIn('type', [SenderType::AlphaNumeric->value, SenderType::Shortcode->value])
            ->when($this->gatewayFilter !== '', fn ($query) => $query->where('gateway_id', $this->gatewayFilter))
            ->when($this->assignmentFilter === 'unused', fn ($query) => $query->doesntHave('users'))
            ->when($this->assignmentFilter === 'assigned', fn ($query) => $query->has('users'))
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('sender_id', 'like', "%{$this->search}%")
                        ->orWhereHas('gateway', fn ($query) => $query->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('id')
            ->paginate(20);

        return view('livewire.sender-cli.masking-list', [
            'senders' => $senders,
            'gateways' => Gateway::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
