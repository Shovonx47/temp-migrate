<?php

use App\Enums\GatewayDriver;
use App\Enums\UserType;
use App\Filament\Admin\Pages\SenderCli\MaskingListPage;
use App\Filament\Admin\Pages\SenderCli\NonMaskingListPage;
use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\CreateGateway;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\EditGateway;
use App\Filament\Admin\Resources\Settings\Gateways\Schemas\GatewayForm;
use App\Filament\Admin\Resources\Settings\Senders\SenderResource;
use App\Models\Gateway;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('enabled carriers config uses free-typed tags instead of preset dropdown options', function () {
    $field = config('sms.drivers.infozillion.credentials.enabled_carriers');

    expect($field['type'])->toBe('tags')
        ->and($field)->not->toHaveKey('options');
});

test('getDriverOptions only exposes the InfoZillion driver by default', function () {
    $options = GatewayForm::getDriverOptions();

    expect($options->all())->toBe([
        GatewayDriver::Infozillion->value => 'InfoZillion (MNPSP)',
    ]);
});

test('getDriverOptions includes an existing non-InfoZillion driver when editing', function () {
    $options = GatewayForm::getDriverOptions(GatewayDriver::Log->value);

    expect($options)
        ->toHaveCount(2)
        ->and($options->has(GatewayDriver::Infozillion->value))->toBeTrue()
        ->and($options->has(GatewayDriver::Log->value))->toBeTrue();
});

test('edit gateway form saves any typed enabled carrier short codes', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->create([
        'type' => UserType::Superman,
        'phone' => '+8801711111178',
    ]);
    actingAs($admin, 'superman');

    $gateway = Gateway::factory()->create([
        'driver' => GatewayDriver::Infozillion,
        'credentials' => [
            'base_url' => 'https://api.mnpspbd.com',
            'api_type' => 'iptsp',
            'username' => 'test_user',
            'password' => 'test_pass',
            'bill_msisdn' => '8801700000000',
            'api_key' => 'test-key',
            'enabled_carriers' => ['ADN'],
        ],
    ]);

    Livewire::test(EditGateway::class, ['record' => $gateway->id])
        ->fillForm([
            'credentials.enabled_carriers' => ['XX', 'YY'],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $gateway->refresh();

    expect($gateway->credentials['enabled_carriers'])->toBe(['XX', 'YY']);
});

test('create gateway form uses InfoZillion defaults', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->create([
        'type' => UserType::Superman,
        'phone' => '+8801711111177',
    ]);
    actingAs($admin, 'superman');

    Livewire::test(CreateGateway::class, ['resource' => GatewayResource::class])
        ->assertFormSet([
            'driver' => GatewayDriver::Infozillion->value,
            'requests_per_second' => 60,
            'timeout_seconds' => 2,
            'is_enabled' => true,
            'dlr_support' => true,
            'dipping_required' => true,
            'credentials.base_url' => 'https://api.mnpspbd.com',
            'credentials.api_key' => 'VQAzoXHxqsiHljWUfY5mk2lcIKIOFMmw',
        ]);
});

test('sender cli navigation labels match the requested names', function () {
    expect(MaskingListPage::getNavigationLabel())->toBe('Masking Sender')
        ->and(NonMaskingListPage::getNavigationLabel())->toBe('Non-Masking Sender')
        ->and(SenderResource::getNavigationLabel())->toBe('Add Sender');
});
