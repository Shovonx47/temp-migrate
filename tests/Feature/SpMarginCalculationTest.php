<?php

use App\Models\User;
use App\Services\Analytics\SpMarginCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('sp margin calculation scopes messages to the sps customers', function () {
    $sp = User::factory()->sp()->create();
    $otherSp = User::factory()->sp()->create();
    $customer = User::factory()->customerOf($sp)->create();
    $otherCustomer = User::factory()->customerOf($otherSp)->create();

    DB::table('messages')->insert([
        [
            'user_id' => $customer->id,
            'to' => '+8801711111111',
            'body' => 'Hello',
            'status' => 'sent',
            'cost' => 2.5,
            'rate' => 0.5,
            'parts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'user_id' => $otherCustomer->id,
            'to' => '+8801722222222',
            'body' => 'Other',
            'status' => 'sent',
            'cost' => 9.9,
            'rate' => 0.5,
            'parts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $result = app(SpMarginCalculationService::class)->calculateForSp(
        $sp,
        now()->subDay(),
        now()->addDay(),
    );

    expect($result['messages'])->toBe(1)
        ->and($result['revenue'])->toEqual(2.5);
});
