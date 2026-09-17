<?php

use App\Enums\GatewayDriver;
use App\Enums\UserType;
use App\Filament\Admin\Resources\Settings\Gateways\GatewayResource;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\EditGateway;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\DippingChargesRelationManager;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\OperatorRatesRelationManager;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\ResolvesGatewayApiData;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\ResolvesGatewayApiType;
use App\Models\Gateway;
use App\Models\GatewayOperatorRate;
use App\Models\Operator;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function makeInfozillionGateway(string $apiType = 'iptsp'): Gateway
{
    return Gateway::factory()->create([
        'driver' => GatewayDriver::Infozillion,
        'credentials' => [
            'api_type' => $apiType,
            'base_url' => 'https://api.mnpspbd.com',
            'api_key' => 'test-key',
            'username' => 'user',
            'password' => 'secret',
            'bill_msisdn' => '8801700000000',
        ],
    ]);
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->create([
        'type' => UserType::Superman,
        'phone' => '+8801711111188',
    ]);
    actingAs($admin, 'superman');
});

test('gateway relation managers can autoload the api-type trait and its live-server alias', function () {
    expect(trait_exists(ResolvesGatewayApiType::class))->toBeTrue()
        ->and(trait_exists(ResolvesGatewayApiData::class))->toBeTrue()
        ->and(class_uses_recursive(OperatorRatesRelationManager::class))->toContain(ResolvesGatewayApiType::class)
        ->and(class_uses_recursive(DippingChargesRelationManager::class))->toContain(ResolvesGatewayApiType::class)
        ->and(class_uses_recursive(ResolvesGatewayApiData::class))->toContain(ResolvesGatewayApiType::class);
});

test('gateway sms api type treats blank values as iptsp and mno as masking-capable', function () {
    $iptsp = makeInfozillionGateway('iptsp');
    $mno = makeInfozillionGateway('mno');
    $blank = Gateway::factory()->create(['credentials' => null]);

    expect($iptsp->smsApiType())->toBe('iptsp')
        ->and($iptsp->usesMaskingRates())->toBeFalse()
        ->and($mno->smsApiType())->toBe('mno')
        ->and($mno->usesMaskingRates())->toBeTrue()
        ->and($blank->smsApiType())->toBe('iptsp')
        ->and($blank->usesMaskingRates())->toBeFalse();
});

test('operator rates table never shows the global operator base rate column', function () {
    $gateway = makeInfozillionGateway('iptsp');
    $operator = Operator::factory()->create(['non_masking_price' => 0.4000]);

    GatewayOperatorRate::query()->create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'non_masking_price' => 0.16,
    ]);

    Livewire::test(OperatorRatesRelationManager::class, [
        'ownerRecord' => $gateway,
        'pageClass' => EditGateway::class,
    ])
        ->assertSee('IPTSP Operator Rate')
        ->assertDontSee('Operator Base Rate')
        ->assertDontSee('InfoZillion Rate')
        ->assertDontSee('0.4000');
});

test('operator rates labels and masking columns follow api type in realtime', function () {
    $gateway = makeInfozillionGateway('iptsp');
    $operator = Operator::factory()->create();

    GatewayOperatorRate::query()->create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'masking_price' => 0.18,
        'non_masking_price' => 0.16,
    ]);

    Livewire::test(OperatorRatesRelationManager::class, [
        'ownerRecord' => $gateway,
        'pageClass' => EditGateway::class,
    ])
        ->assertSee('IPTSP Operator Rate')
        ->assertDontSee('MNO Operator Rate')
        ->assertDontSee('Masking Price')
        ->call('onGatewayApiTypeChanged', 'mno')
        ->assertSee('MNO Operator Rate')
        ->assertDontSee('IPTSP Operator Rate')
        ->assertSee('Masking Price')
        ->call('onGatewayApiTypeChanged', 'iptsp')
        ->assertSee('IPTSP Operator Rate')
        ->assertDontSee('MNO Operator Rate');
});

test('edit gateway stacks billing relation managers instead of splitting them into tabs', function () {
    $gateway = makeInfozillionGateway('iptsp');

    $managers = collect(GatewayResource::getRelations());

    expect($managers)->toHaveCount(1)
        ->and($managers->first())->toBeInstanceOf(RelationGroup::class);

    $content = Livewire::test(EditGateway::class, ['record' => $gateway->id])
        ->instance()
        ->getRelationManagersContentComponent();

    expect($content)->toBeInstanceOf(Group::class)
        ->and($content)->not->toBeInstanceOf(Tabs::class);
});

test('changing api type on the gateway form broadcasts the live operator rate label', function () {
    $gateway = makeInfozillionGateway('iptsp');

    Livewire::test(EditGateway::class, ['record' => $gateway->id])
        ->set('data.credentials.api_type', 'mno')
        ->assertDispatched('gateway-api-type-changed', apiType: 'mno')
        ->set('data.credentials.api_type', 'iptsp')
        ->assertDispatched('gateway-api-type-changed', apiType: 'iptsp');
});
