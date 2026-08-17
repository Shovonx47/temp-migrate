<?php

use App\Actions\Senders\ApproveSenderRequest;
use App\Actions\Senders\RejectSenderRequest;
use App\Enums\SenderApprovalStatus;
use App\Models\Gateway;
use App\Models\Sender;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('approving a pending sender request enables it for the sp', function () {
    $sp = User::factory()->sp()->create();
    $admin = User::factory()->superman()->create();
    $gateway = Gateway::factory()->create();

    $sender = Sender::factory()->pendingRequest($sp->id)->create();
    $sender->users()->attach($sp);

    app(ApproveSenderRequest::class)->handle($sender, $admin, $gateway->id);

    $sender->refresh();

    expect($sender->approval_status)->toBe(SenderApprovalStatus::Approved)
        ->and($sender->is_enabled)->toBeTrue()
        ->and($sender->gateway_id)->toBe($gateway->id)
        ->and($sender->reviewed_by)->toBe($admin->id)
        ->and($sender->reviewed_at)->not->toBeNull();
});

test('rejecting a pending sender request records the reason', function () {
    $sp = User::factory()->sp()->create();
    $admin = User::factory()->superman()->create();

    $sender = Sender::factory()->pendingRequest($sp->id)->create();

    app(RejectSenderRequest::class)->handle($sender, $admin, 'Invalid documentation');

    $sender->refresh();

    expect($sender->approval_status)->toBe(SenderApprovalStatus::Rejected)
        ->and($sender->rejection_reason)->toBe('Invalid documentation')
        ->and($sender->is_enabled)->toBeFalse()
        ->and($sender->reviewed_by)->toBe($admin->id);
});
