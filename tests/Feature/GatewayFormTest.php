<?php

use App\Enums\GatewayDriver;
use App\Enums\UserType;
use App\Filament\Admin\Pages\SenderCli\MaskingListPage;
use App\Filament\Admin\Pages\SenderCli\NonMaskingListPage;
use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\CreateGateway;
use App\Filament\Admin\Resources\Settings\Gateways\Schemas\GatewayForm;
use App\Filament\Admin\Resources\Settings\Senders\SenderResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('getCarrierOptionsForApiType returns exact 20 IPTSP carrier options when api_type is iptsp', function () {
    $options = GatewayForm::getCarrierOptionsForApiType('iptsp');

    expect($options)->toBe([
        'ADN' => 'ADN',
        'MN' => 'MN',
        'BN' => 'BN',
        'AIT' => 'AIT',
        'RT' => 'RT',
        'PRM' => 'PRM',
        'FN' => 'FN',
        'BR' => 'BR',
        'MTN' => 'MTN',
        'RCO' => 'RCO',
        'BPL' => 'BPL',
        'ICN' => 'ICN',
        'WEB' => 'WEB',
        'BDC' => 'BDC',
        'RDT' => 'RDT',
        'L3' => 'L3',
        'BTCL' => 'BTCL',
        'AGNI' => 'AGNI',
        'ICC' => 'ICC',
        'RG' => 'RG',
    ]);
});

test('getCarrierOptionsForApiType returns exact 4 MNO carrier options when api_type is mno', function () {
    $options = GatewayForm::getCarrierOptionsForApiType('mno');

    expect($options)->toBe([
        'GP' => 'GP',
        'BL' => 'BL',
        'RB' => 'RB',
        'TT' => 'TT',
    ]);
});

test('enabled carrier option keys stay as InfoZillion short codes used by the driver', function () {
    $iptspCodes = array_keys(GatewayForm::getIptspCarrierOptions());
    $mnoCodes = array_keys(GatewayForm::getMnoCarrierOptions());

    expect($iptspCodes)->toContain('RT', 'MN', 'BN', 'ADN')
        ->and($mnoCodes)->toBe(['GP', 'BL', 'RB', 'TT'])
        ->and(array_values(GatewayForm::getIptspCarrierOptions()))->toBe($iptspCodes)
        ->and(array_values(GatewayForm::getMnoCarrierOptions()))->toBe($mnoCodes);
});

test('getCarrierOptionsForApiType defaults to IPTSP options when api_type is null or blank', function () {
    $optionsNull = GatewayForm::getCarrierOptionsForApiType(null);
    $optionsBlank = GatewayForm::getCarrierOptionsForApiType('');

    expect($optionsNull)->toHaveCount(20)
        ->and($optionsBlank)->toHaveCount(20);
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
