<?php

use App\Filament\Admin\Resources\Settings\Gateways\Schemas\GatewayForm;

test('getCarrierOptionsForApiType returns exact 20 IPTSP carrier options when api_type is iptsp', function () {
    $options = GatewayForm::getCarrierOptionsForApiType('iptsp');

    expect($options)->toBe([
        'ADN' => 'ADN',
        'MN' => 'Mirnet',
        'BN' => 'Bracnet',
        'AIT' => 'AmberIT',
        'RT' => 'RanksITT',
        'PRM' => 'Premium',
        'FN' => 'FusionNet',
        'BR' => 'Brilliant',
        'MTN' => 'Metronet',
        'RCO' => 'RaceOnline',
        'BPL' => 'BanglaPhone',
        'ICN' => 'Icon',
        'WEB' => 'Weblink',
        'BDC' => 'BDCOM',
        'RDT' => 'RedData',
        'L3' => 'Link3',
        'BTCL' => 'BTCL',
        'AGNI' => 'AGNI',
        'ICC' => 'ICC',
        'RG' => 'RoyalGreen',
    ]);
});

test('getCarrierOptionsForApiType returns exact 4 MNO carrier options when api_type is mno', function () {
    $options = GatewayForm::getCarrierOptionsForApiType('mno');

    expect($options)->toBe([
        'GP' => 'Grameenphone',
        'BL' => 'Banglalink',
        'RB' => 'Robi',
        'TT' => 'Teletalk',
    ]);
});

test('getCarrierOptionsForApiType defaults to IPTSP options when api_type is null or blank', function () {
    $optionsNull = GatewayForm::getCarrierOptionsForApiType(null);
    $optionsBlank = GatewayForm::getCarrierOptionsForApiType('');

    expect($optionsNull)->toHaveCount(20)
        ->and($optionsBlank)->toHaveCount(20);
});
