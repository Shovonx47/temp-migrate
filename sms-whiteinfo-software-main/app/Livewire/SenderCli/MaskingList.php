<?php

namespace App\Livewire\SenderCli;

use App\Enums\SenderType;
use App\Enums\UserType;
use App\Models\Gateway;
use App\Models\Sender;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class MaskingList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $gatewayFilter = '';

    public string $assignmentFilter = '';

    public ?int $selectedSenderId = null;

    /** @var array<int, int> */
    public array $selectedCustomerIds = [];

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'gatewayFilter', 'assignmentFilter'], true)) {
            $this->resetPage();
        }
    }

    public function openAssignmentModal(?int $senderId = null): void
    {
        $this->resetValidation();
        $this->selectedSenderId = $senderId;
        $this->selectedCustomerIds = $senderId === null
            ? []
            : $this->senderQuery()
                ->findOrFail($senderId)
                ->users()
                ->where('type', UserType::Customer->value)
                ->pluck('users.id')
                ->all();

        $this->dispatch('open-modal', id: 'sender-assignment-modal');
    }

    public function updatedSelectedSenderId(?int $senderId): void
    {
        $this->selectedCustomerIds = $senderId === null
            ? []
            : $this->senderQuery()
                ->findOrFail($senderId)
                ->users()
                ->where('type', UserType::Customer->value)
                ->pluck('users.id')
                ->all();
    }

    public function saveAssignment(): void
    {
        $validated = $this->validate([
            'selectedSenderId' => ['required', Rule::exists('senders', 'id')],
            'selectedCustomerIds' => ['required', 'array', 'min:1'],
            'selectedCustomerIds.*' => [
                'integer',
                Rule::exists('users', 'id')->where('type', UserType::Customer->value),
            ],
        ]);

        $sender = $this->senderQuery()->findOrFail($validated['selectedSenderId']);
        $sender->users()->syncWithoutDetaching($validated['selectedCustomerIds']);

        $this->reset('selectedSenderId', 'selectedCustomerIds');
        $this->resetValidation();
        $this->dispatch('close-modal', id: 'sender-assignment-modal');

        Notification::make()
            ->success()
            ->title('Sender ID assigned')
            ->body('The selected customer access has been saved.')
            ->send();
    }

    public function render()
    {
        $sendersQuery = $this->senderQuery()
            ->with(['gateway:id,name', 'users:id,name'])
            ->withCount(['users', 'messages'])
            ->when($this->gatewayFilter !== '', fn (Builder $query) => $query->where('gateway_id', $this->gatewayFilter));

        if ($this->assignmentFilter === 'unused') {
            $sendersQuery->doesntHave('users');
        } elseif ($this->assignmentFilter === 'used') {
            $sendersQuery->has('users');
        }

        if ($this->search !== '') {
            $sendersQuery->where(function (Builder $query): void {
                $query->where('sender_id', 'like', "%{$this->search}%")
                    ->orWhereHas('gateway', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%"));
            });
        }

        $senders = $sendersQuery->latest('id')->paginate(20);

        return view('livewire.sender-cli.masking-list', [
            'senders' => $senders,
            'gateways' => Gateway::query()->orderBy('name')->get(['id', 'name']),
            'availableSenders' => $this->senderQuery()->orderBy('sender_id')->get(['id', 'sender_id']),
            'customers' => User::query()
                ->customers()
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    protected function senderQuery(): Builder
    {
        return Sender::query()->whereIn('type', [SenderType::AlphaNumeric->value, SenderType::Shortcode->value]);
    }
}
