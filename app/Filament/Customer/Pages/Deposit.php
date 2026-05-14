<?php

namespace App\Filament\Customer\Pages;

use App\Models\Deposit as DepositModel;
use App\Services\Payments\PaymentManager;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\RedirectResponse;

class Deposit extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Add Balance';

    protected static ?string $title = 'Add Balance';

    protected ?string $heading = 'Add Balance';

    protected string $view = 'filament.customer.pages.deposit';

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('amount')
                    ->label('Amount (BDT)')
                    ->numeric()
                    ->required()
                    ->minValue(10)
                    ->maxValue(50000)
                    ->helperText('Minimum 10 BDT, maximum 50,000 BDT'),
            ])
            ->statePath('data');
    }

    public function submit(): RedirectResponse
    {
        $this->validate([
            'data.amount' => ['required', 'numeric', 'min:10', 'max:50000'],
        ]);

        $user = auth()->user();

        $deposit = DepositModel::create([
            'user_id' => $user->id,
            'amount' => $this->data['amount'],
            'currency' => 'BDT',
            'payment_method' => 'piprapay',
            'status' => 'pending',
        ]);

        $manager = app(PaymentManager::class);

        return $manager->driver('piprapay')->initiate($deposit);
    }
}
