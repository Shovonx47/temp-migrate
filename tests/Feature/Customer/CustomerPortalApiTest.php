<?php

declare(strict_types=1);

use App\Enums\SenderApprovalStatus;
use App\Enums\SenderType;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->customer = User::factory()->create([
        'type' => UserType::Customer,
        'status' => UserStatus::Approved,
        'balance' => 1000.0,
    ]);
});

test('customer can retrieve profile info via api', function () {
    actingAs($this->customer, 'customer');

    $response = $this->getJson(route('api.v1.customer.me'));

    $response->assertOk()
        ->assertJsonPath('data.email', $this->customer->email);
});

test('customer can create and list templates via api', function () {
    actingAs($this->customer, 'customer');

    $createResponse = $this->postJson(route('api.v1.customer.templates.store'), [
        'name' => 'Welcome SMS',
        'body' => 'Welcome {name} to our service!',
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('data.name', 'Welcome SMS')
        ->assertJsonPath('data.variables', ['name']);

    $listResponse = $this->getJson(route('api.v1.customer.templates.index'));
    $listResponse->assertOk()
        ->assertJsonCount(1, 'data');
});

test('customer can create and list contacts and groups via api', function () {
    actingAs($this->customer, 'customer');

    $groupResponse = $this->postJson(route('api.v1.customer.groups.store'), [
        'name' => 'VIP Customers',
    ]);
    $groupResponse->assertCreated()
        ->assertJsonPath('data.name', 'VIP Customers');

    $groupId = $groupResponse->json('data.id');

    $contactResponse = $this->postJson(route('api.v1.customer.contacts.store'), [
        'name' => 'Jane Doe',
        'phone' => '+8801700000000',
        'group_id' => $groupId,
    ]);
    $contactResponse->assertCreated()
        ->assertJsonPath('data.name', 'Jane Doe');

    $listResponse = $this->getJson(route('api.v1.customer.contacts.index'));
    $listResponse->assertOk()
        ->assertJsonCount(1, 'data');
});

test('customer can request a sender id via api', function () {
    actingAs($this->customer, 'customer');

    $response = $this->postJson(route('api.v1.customer.senders.store'), [
        'sender_id' => 'MYBRAND',
        'type' => 'masking',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.sender_id', 'MYBRAND')
        ->assertJsonPath('data.approval_status', 'pending');
});

test('customer can create and revoke personal api tokens', function () {
    actingAs($this->customer, 'customer');

    $createResponse = $this->postJson(route('api.v1.customer.tokens.store'), [
        'name' => 'Test Token',
    ]);

    $createResponse->assertCreated()
        ->assertJsonStructure(['token', 'data' => ['id', 'name']]);

    $tokenId = $createResponse->json('data.id');

    $deleteResponse = $this->deleteJson(route('api.v1.customer.tokens.destroy', ['id' => $tokenId]));
    $deleteResponse->assertOk();
});
