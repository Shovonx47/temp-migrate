<?php

use App\Enums\SenderType;
use App\Enums\UserType;
use App\Filament\Admin\Resources\Settings\Senders\Pages\CreateSender;
use App\Filament\Admin\Resources\Settings\Senders\Pages\ListSenders;
use App\Filament\Admin\Resources\Settings\Senders\SenderResource;
use App\Models\Gateway;
use App\Models\Sender;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Filament::setCurrentPanel(Filament::getPanel('superman'));

    $this->admin = User::factory()->create([
        'type' => UserType::Superman,
        'phone' => '+8801711111111',
    ]);
    actingAs($this->admin, 'superman');

    $this->gateway = Gateway::factory()->create([
        'name' => 'Infozillion MNO',
        'is_enabled' => true,
    ]);
});

test('can bulk import senders from CSV file on create sender page', function () {
    $customer1 = User::factory()->create(['type' => 'customer', 'phone' => '+8801722222222']);
    $customer2 = User::factory()->create(['type' => 'customer', 'phone' => '+8801733333333']);

    $csvContent = "sender_id,type,gateway\nFoodPanda,masking,Infozillion MNO\n8801700000000,non-masking,Infozillion MNO\n";
    $file = UploadedFile::fake()->createWithContent('senders.csv', $csvContent);
    $storedPath = $file->store('uploads/sender-imports', 'local');

    Livewire::test(CreateSender::class, ['resource' => SenderResource::class])
        ->fillForm([
            'creation_mode' => 'bulk',
            'file' => [$storedPath],
            'gateway_id' => $this->gateway->id,
            'type' => SenderType::MaskingCategory,
            'is_enabled' => true,
            'users' => [$customer1->id, $customer2->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $foodPanda = Sender::where('sender_id', 'FoodPanda')->first();
    expect($foodPanda)->not->toBeNull()
        ->and($foodPanda->type)->toBe(SenderType::AlphaNumeric)
        ->and($foodPanda->gateway_id)->toBe($this->gateway->id);

    $numericSender = Sender::where('sender_id', '8801700000000')->first();
    expect($numericSender)->not->toBeNull()
        ->and($numericSender->type)->toBe(SenderType::Numeric);
});

test('can bulk import senders from sample CliList csv format', function () {
    $sampleCsv = file_get_contents(public_path('CliList.csv'));
    $file = UploadedFile::fake()->createWithContent('CliList.csv', $sampleCsv);
    $storedPath = $file->store('uploads/sender-imports', 'local');

    Livewire::test(CreateSender::class, ['resource' => SenderResource::class])
        ->fillForm([
            'creation_mode' => 'bulk',
            'file' => [$storedPath],
            'gateway_id' => $this->gateway->id,
            'type' => SenderType::NonMaskingCategory,
            'is_enabled' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Sender::whereIn('sender_id', [
        '8809617639964',
        '8809617639963',
        '8809617639962',
        '8809617639961',
    ])->count())->toBe(4);

    $sender = Sender::where('sender_id', '8809617639964')->first();
    expect($sender)->not->toBeNull()
        ->and($sender->type)->toBe(SenderType::Numeric);
});

test('create sender bulk form shows sample csv download link', function () {
    Livewire::test(CreateSender::class, ['resource' => SenderResource::class])
        ->fillForm(['creation_mode' => 'bulk'])
        ->assertSee('Download sample CSV')
        ->assertSee(asset('CliList.csv'));
});

test('create sender form shows masking and non-masking type options', function () {
    Livewire::test(CreateSender::class, ['resource' => SenderResource::class])
        ->assertSee('Masking')
        ->assertSee('Non-masking')
        ->assertDontSee('Alpha Numeric')
        ->assertDontSee('Shortcode');
});

test('can create single sender normally on create sender page', function () {
    Livewire::test(CreateSender::class, ['resource' => SenderResource::class])
        ->fillForm([
            'creation_mode' => 'single',
            'sender_id' => 'MyBrand',
            'gateway_id' => $this->gateway->id,
            'type' => SenderType::MaskingCategory,
            'is_enabled' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $sender = Sender::where('sender_id', 'MyBrand')->first();
    expect($sender)->not->toBeNull()
        ->and($sender->type)->toBe(SenderType::AlphaNumeric)
        ->and($sender->gateway_id)->toBe($this->gateway->id);
});

test('list senders page renders without error', function () {
    Livewire::test(ListSenders::class, ['resource' => SenderResource::class])
        ->assertSuccessful();
});

test('list senders page shows masking and non-masking type labels', function () {
    Sender::factory()->create([
        'gateway_id' => $this->gateway->id,
        'sender_id' => 'BRANDNAME',
        'type' => SenderType::AlphaNumeric,
    ]);

    Sender::factory()->create([
        'gateway_id' => $this->gateway->id,
        'sender_id' => '8801700000001',
        'type' => SenderType::Numeric,
    ]);

    Livewire::test(ListSenders::class, ['resource' => SenderResource::class])
        ->assertSee('Masking')
        ->assertSee('Non-masking')
        ->assertDontSee('Alpha Numeric')
        ->assertDontSee('Shortcode')
        ->assertDontSee('Numeric');
});
