<?php

namespace App\Livewire;

use App\Filament\Customer\Resources\Billing\Deposits\Actions\DepositAction;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\View\View;
use Livewire\Component;

class BalanceButton extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public float $balance = 0;

    public function mount(): void
    {
        $this->balance = (float) (auth()->user()?->balance ?? 0);
    }

    public function refreshBalance(): void
    {
        $this->balance = (float) (auth()->user()?->balance ?? 0);
    }

    public function depositAction(): Action
    {
        return DepositAction::make();
    }

    public function render(): View
    {
        return view('livewire.balance-button');
    }
}
