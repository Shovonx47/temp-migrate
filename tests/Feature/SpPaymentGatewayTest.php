<?php

use App\Actions\Billing\CompleteDeposit;
use App\Enums\DepositPaymentMethod;
use App\Enums\DepositStatus;
use App\Enums\UserType;
use App\Filament\Admin\Pages\Resellers\ResellerPaymentGatewayPage;
use App\Filament\Sp\Pages\Wallet;
use App\Filament\Sp\Resources\Billing\Deposits\DepositResource;
use App\Filament\Sp\Resources\Billing\Deposits\Pages\ListDeposits;
use App\Filament\Sp\Resources\Billing\Deposits\Pages\ViewDeposit;
use App\Models\Deposit;
use App\Models\User;
use App\Services\Payment\Data\PaymentResponse;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('sp can view their deposits list on sp panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create(['balance' => 500]);
    $otherSp = User::factory()->sp()->create();

    $myDeposit = Deposit::factory()->create([
        'user_id' => $sp->id,
        'amount' => 200,
        'status' => DepositStatus::Completed,
    ]);

    $otherDeposit = Deposit::factory()->create([
        'user_id' => $otherSp->id,
        'amount' => 300,
        'status' => DepositStatus::Completed,
    ]);

    actingAs($sp, 'sp');

    Livewire::test(ListDeposits::class)
        ->assertCanSeeTableRecords([$myDeposit])
        ->assertCanNotSeeTableRecords([$otherDeposit]);
});

test('sp can view an individual deposit on sp panel', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create();

    $deposit = Deposit::factory()->create([
        'user_id' => $sp->id,
        'amount' => 500,
        'status' => DepositStatus::Completed,
    ]);

    actingAs($sp, 'sp');

    Livewire::test(ViewDeposit::class, ['record' => $deposit->id])
        ->assertSuccessful();
});

test('payment callback redirects sp user to sp panel deposit view', function () {
    $sp = User::factory()->sp()->create();

    $deposit = Deposit::factory()->create([
        'user_id' => $sp->id,
        'payment_method' => DepositPaymentMethod::PipraPay,
        'status' => DepositStatus::Completed,
    ]);

    $response = $this->actingAs($sp, 'sp')
        ->get(route('payments.callback', [
            'gateway' => 'piprapay',
            'deposit' => $deposit->id,
        ]));

    $response->assertRedirect(DepositResource::getUrl('view', [
        'record' => $deposit,
    ], panel: 'sp'));
});

test('payment callback redirects customer to customer panel deposit view', function () {
    $customer = User::factory()->create(['type' => UserType::Customer]);

    $deposit = Deposit::factory()->create([
        'user_id' => $customer->id,
        'payment_method' => DepositPaymentMethod::PipraPay,
        'status' => DepositStatus::Completed,
    ]);

    $response = $this->actingAs($customer, 'customer')
        ->get(route('payments.callback', [
            'gateway' => 'piprapay',
            'deposit' => $deposit->id,
        ]));

    $response->assertRedirect(App\Filament\Customer\Resources\Billing\Deposits\DepositResource::getUrl('view', [
        'record' => $deposit,
    ], panel: 'customer'));
});

test('payment webhook completes deposit and credits sp balance', function () {
    $sp = User::factory()->sp()->create(['balance' => 100]);

    $deposit = Deposit::factory()->create([
        'user_id' => $sp->id,
        'amount' => 500,
        'payment_method' => DepositPaymentMethod::PipraPay,
        'transaction_id' => 'TRX-SP-999',
        'status' => DepositStatus::Pending,
    ]);

    $paymentResponse = new PaymentResponse(
        successful: true,
        gatewayDriver: 'piprapay',
        gatewayStatus: 'completed',
        gatewayPaymentId: 'TRX-SP-999',
        amount: 500,
        currency: 'BDT',
    );

    $completed = app(CompleteDeposit::class)->handle('piprapay', $paymentResponse);

    expect($completed)->toBeTrue();
    expect($deposit->fresh()->status)->toBe(DepositStatus::Completed);
    expect($sp->fresh()->balance)->toBe(600.0);
});

test('admin can view sp deposits on reseller payment gateway page', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->superman()->create();
    $sp = User::factory()->sp()->create(['name' => 'Apex S P']);
    $customer = User::factory()->create(['type' => UserType::Customer, 'name' => 'Regular Customer']);

    $spDeposit = Deposit::factory()->create([
        'user_id' => $sp->id,
        'amount' => 1000,
        'status' => DepositStatus::Completed,
    ]);

    $customerDeposit = Deposit::factory()->create([
        'user_id' => $customer->id,
        'amount' => 50,
        'status' => DepositStatus::Completed,
    ]);

    actingAs($admin, 'superman');

    Livewire::test(ResellerPaymentGatewayPage::class)
        ->assertCanSeeTableRecords([$spDeposit])
        ->assertCanNotSeeTableRecords([$customerDeposit]);
});

test('wallet page renders top up action', function () {
    Filament::setCurrentPanel(Filament::getPanel('sp'));

    $sp = User::factory()->sp()->create();
    actingAs($sp, 'sp');

    Livewire::test(Wallet::class)
        ->assertActionExists('topUp');
});

test('customer under sp top-up debits sp balance and credits customer balance', function () {
    $sp = User::factory()->sp()->create(['balance' => 10000]);
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'sp_id' => $sp->id,
        'balance' => 0,
    ]);

    $deposit = Deposit::factory()->create([
        'user_id' => $customer->id,
        'amount' => 50,
        'payment_method' => DepositPaymentMethod::PipraPay,
        'transaction_id' => 'TRX-CUST-50',
        'status' => DepositStatus::Pending,
    ]);

    $paymentResponse = new PaymentResponse(
        successful: true,
        gatewayDriver: 'piprapay',
        gatewayStatus: 'completed',
        gatewayPaymentId: 'TRX-CUST-50',
        amount: 50,
        currency: 'BDT',
    );

    $completed = app(CompleteDeposit::class)->handle('piprapay', $paymentResponse);

    expect($completed)->toBeTrue();
    expect($deposit->fresh()->status)->toBe(DepositStatus::Completed);
    expect($customer->fresh()->balance)->toBe(50.0);
    expect($sp->fresh()->balance)->toBe(9950.0);
});

test('standalone customer top-up credits customer without touching any sp', function () {
    $customer = User::factory()->create([
        'type' => UserType::Customer,
        'sp_id' => null,
        'balance' => 100,
    ]);

    $deposit = Deposit::factory()->create([
        'user_id' => $customer->id,
        'amount' => 200,
        'payment_method' => DepositPaymentMethod::PipraPay,
        'transaction_id' => 'TRX-DIRECT-200',
        'status' => DepositStatus::Pending,
    ]);

    $paymentResponse = new PaymentResponse(
        successful: true,
        gatewayDriver: 'piprapay',
        gatewayStatus: 'completed',
        gatewayPaymentId: 'TRX-DIRECT-200',
        amount: 200,
        currency: 'BDT',
    );

    $completed = app(CompleteDeposit::class)->handle('piprapay', $paymentResponse);

    expect($completed)->toBeTrue();
    expect($deposit->fresh()->status)->toBe(DepositStatus::Completed);
    expect($customer->fresh()->balance)->toBe(300.0);
});
