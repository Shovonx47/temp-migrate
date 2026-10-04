<?php

use App\Enums\GatewayDriver;
use App\Enums\MessageSource;
use App\Enums\UserType;
use App\Filament\Admin\Pages\Reports\SmsReportsPage;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('superman'));
    actingAs(User::factory()->superman()->create(), 'superman');
});

test('sms report displays every required transaction column with stored provider values', function (): void {
    $customer = User::factory()->create(['type' => UserType::Customer, 'name' => 'API Customer']);
    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Infozillion,
        'credentials' => [
            'api_type' => 'mno',
            'bill_msisdn' => '8801960000000',
        ],
    ]);
    $sender = Sender::factory()->for($gateway)->create(['sender_id' => 'WHITEINFO']);
    $message = Message::factory()->for($customer)->for($gateway)->for($sender)->create([
        'to' => '+8801712345678',
        'body' => 'Your verification code is 1234.',
        'source' => MessageSource::API,
        'bill_msisdn' => '8801960000000',
        'sms_type' => 'T',
        'channel' => 'MNO',
        'operator_name' => 'Grameenphone',
        'tariff_name' => 'Enterprise Tariff',
        'request_id' => '943b5af0-c5bd-4a3b-83f8-30d0c3a1c93e',
        'server_txn_id' => 'provider-server-transaction',
        'server_response_code' => '9000',
        'mno_txn_id' => 'mno-transaction',
        'mno_response_code' => '1000',
        'mno_response_message' => 'Success',
    ]);

    Livewire::test(SmsReportsPage::class)
        ->assertCanSeeTableRecords([$message])
        ->assertSee('Recipient Number')
        ->assertSee('Bill MSISDN')
        ->assertSee('Previous Balance')
        ->assertSee('MNO Response Message')
        ->assertSee('provider-server-transaction')
        ->assertSee('API Customer');
});
