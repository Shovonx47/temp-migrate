<?php

use App\Filament\Customer\Resources\Messaging\DeliveryReports\DeliveryReportResource;
use App\Filament\Customer\Resources\Messaging\DeliveryReports\Pages\ListDeliveryReports;
use App\Filament\Customer\Resources\Messaging\DeliveryReports\Pages\ViewDeliveryReport;
use App\Models\DeliveryReport;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their delivery reports', function () {
    $user = User::factory()->create();
    $message = Message::factory()->for($user)->create();
    $reports = DeliveryReport::factory()->for($message)->count(3)->create();

    livewire(ListDeliveryReports::class, ['resource' => DeliveryReportResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($reports);
});

test('customer can view a delivery report', function () {
    $user = User::factory()->create();
    $message = Message::factory()->for($user)->create();
    $report = DeliveryReport::factory()->for($message)->create();

    livewire(ViewDeliveryReport::class, ['record' => $report->id, 'resource' => DeliveryReportResource::class])
        ->actingAs($user)
        ->assertSee($report->status->value);
});

test('customer cannot view delivery reports of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $message = Message::factory()->for($other)->create();
    $report = DeliveryReport::factory()->for($message)->create();

    livewire(ViewDeliveryReport::class, ['record' => $report->id, 'resource' => DeliveryReportResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
