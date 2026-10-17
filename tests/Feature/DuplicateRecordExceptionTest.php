<?php

use App\Enums\UserType;
use App\Exceptions\DuplicateRecordHandler;
use App\Filament\Admin\Resources\Settings\Gateways\Pages\EditGateway;
use App\Filament\Admin\Resources\Settings\Gateways\RelationManagers\OperatorRatesRelationManager;
use App\Models\Gateway;
use App\Models\GatewayOperatorRate;
use App\Models\Operator;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function duplicateGatewayOperatorRateException(): UniqueConstraintViolationException
{
    $gateway = Gateway::factory()->create();
    $operator = Operator::factory()->create();

    GatewayOperatorRate::query()->create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'masking_price' => 0.16,
        'non_masking_price' => 0.16,
    ]);

    try {
        GatewayOperatorRate::query()->create([
            'gateway_id' => $gateway->id,
            'operator_id' => $operator->id,
            'masking_price' => 0.20,
            'non_masking_price' => 0.20,
        ]);
    } catch (UniqueConstraintViolationException $exception) {
        return $exception;
    }

    throw new RuntimeException('Expected a unique constraint violation when inserting a duplicate operator rate.');
}

test('duplicate unique constraint violations are identified by the system-wide checker', function () {
    expect(DuplicateRecordHandler::matches(duplicateGatewayOperatorRateException()))->toBeTrue();
});

test('html duplicate unique constraint shows a friendly notice instead of a server error', function () {
    $exception = duplicateGatewayOperatorRateException();

    $request = Request::create('/admin/gateways/1', 'POST');
    $request->headers->set('Referer', 'http://localhost/admin/gateways/1');
    $request->headers->set('Accept', 'text/html');
    $request->headers->set('X-Livewire', 'true');
    $request->setLaravelSession($this->app['session.store']);

    $response = app(ExceptionHandler::class)->render($request, $exception);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->getContent())->not->toContain('SQLSTATE')
        ->and($response->getContent())->not->toContain('UniqueConstraint')
        ->and($response->getContent())->not->toContain('Integrity constraint');

    $notifications = collect(session('filament.notifications') ?? []);

    expect($notifications)->not->toBeEmpty()
        ->and($notifications->toJson())->toContain(DuplicateRecordHandler::TITLE)
        ->and($notifications->toJson())->toContain(DuplicateRecordHandler::MESSAGE);
});

test('api duplicate unique constraint returns a conflict json payload without sql details', function () {
    $exception = duplicateGatewayOperatorRateException();

    $request = Request::create('/api/v1/messages', 'POST');
    $request->headers->set('Accept', 'application/json');

    $response = app(ExceptionHandler::class)->render($request, $exception);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(409)
        ->and($response->getData(true))->toBe([
            'message' => DuplicateRecordHandler::MESSAGE,
        ])
        ->and($response->getContent())->not->toContain('SQLSTATE')
        ->and($response->getContent())->not->toContain('Integrity constraint');
});

test('gateway operator rate create action rejects a duplicate operator without a server error', function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $admin = User::factory()->create([
        'type' => UserType::Superman,
        'phone' => '+8801711111199',
    ]);
    actingAs($admin, 'superman');

    $gateway = Gateway::factory()->create();
    $operator = Operator::factory()->create();

    GatewayOperatorRate::query()->create([
        'gateway_id' => $gateway->id,
        'operator_id' => $operator->id,
        'masking_price' => 0.16,
        'non_masking_price' => 0.16,
    ]);

    Livewire::test(OperatorRatesRelationManager::class, [
        'ownerRecord' => $gateway,
        'pageClass' => EditGateway::class,
    ])
        ->callTableAction('create', data: [
            'operator_id' => $operator->id,
            'masking_price' => 0.20,
            'non_masking_price' => 0.20,
        ])
        ->assertHasTableActionErrors(['operator_id'])
        ->assertDontSee('SQLSTATE')
        ->assertDontSee('UniqueConstraintViolationException');

    expect(GatewayOperatorRate::query()->where('gateway_id', $gateway->id)->count())->toBe(1);
});
