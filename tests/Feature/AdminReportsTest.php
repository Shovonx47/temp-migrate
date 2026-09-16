<?php

use App\Enums\SenderType;
use App\Enums\UserType;
use App\Filament\Admin\Pages\Reports\CustomerReportsPage;
use App\Filament\Admin\Pages\Reports\FinancialReportsPage;
use App\Filament\Admin\Pages\Reports\MaskingReportPage;
use App\Filament\Admin\Pages\Reports\NonMaskingReportPage;
use App\Filament\Admin\Pages\Reports\ProviderReportsPage;
use App\Filament\Admin\Pages\Reports\SmsReportsPage;
use App\Filament\Admin\Pages\SenderCli\MaskingListPage;
use App\Filament\Admin\Pages\SenderCli\NonMaskingListPage;
use App\Livewire\SenderCli\MaskingList;
use App\Livewire\SenderCli\NonMaskingList;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\Sender;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('superman'));
    actingAs(User::factory()->superman()->create(), 'superman');
    $this->gateway = Gateway::factory()->create(['is_enabled' => true]);
    $this->customer = User::factory()->create(['type' => UserType::Customer]);
});

test('all visible report pages render data tables', function (string $page) {
    Livewire::test($page)->assertSuccessful()->assertSee('Export Excel')->assertSee('Export PDF');
})->with([SmsReportsPage::class, MaskingReportPage::class, NonMaskingReportPage::class, CustomerReportsPage::class, ProviderReportsPage::class, FinancialReportsPage::class]);

test('masking and non-masking reports isolate sender types', function () {
    $masking = Sender::factory()->create(['gateway_id' => $this->gateway->id, 'sender_id' => 'WHITEINFO', 'type' => SenderType::AlphaNumeric]);
    $numeric = Sender::factory()->create(['gateway_id' => $this->gateway->id, 'sender_id' => '8801711111111', 'type' => SenderType::Numeric]);
    $maskingMessage = Message::factory()->create(['user_id' => $this->customer->id, 'gateway_id' => $this->gateway->id, 'sender_id' => $masking->id]);
    $numericMessage = Message::factory()->create(['user_id' => $this->customer->id, 'gateway_id' => $this->gateway->id, 'sender_id' => $numeric->id]);

    Livewire::test(MaskingReportPage::class)->assertCanSeeTableRecords([$maskingMessage])->assertCanNotSeeTableRecords([$numericMessage]);
    Livewire::test(NonMaskingReportPage::class)->assertCanSeeTableRecords([$numericMessage])->assertCanNotSeeTableRecords([$maskingMessage]);
});

test('dedicated sender lists show only their type and identify unused senders', function () {
    Sender::factory()->create(['gateway_id' => $this->gateway->id, 'sender_id' => 'UNUSEDMASK', 'type' => SenderType::AlphaNumeric]);
    $assignedNumeric = Sender::factory()->create(['gateway_id' => $this->gateway->id, 'sender_id' => '8801711111111', 'type' => SenderType::Numeric]);
    $assignedNumeric->users()->attach($this->customer);

    Livewire::test(MaskingList::class)->assertSee('UNUSEDMASK')->assertSee('Unused')->assertDontSee('8801711111111');
    Livewire::test(NonMaskingList::class)->assertSee('8801711111111')->assertDontSee('UNUSEDMASK');
    Livewire::test(MaskingListPage::class)->assertSuccessful();
    Livewire::test(NonMaskingListPage::class)->assertSuccessful();
});
