<?php

use App\Filament\Customer\Resources\Messaging\Templates\Pages\CreateTemplate;
use App\Filament\Customer\Resources\Messaging\Templates\Pages\EditTemplate;
use App\Filament\Customer\Resources\Messaging\Templates\Pages\ListTemplates;
use App\Filament\Customer\Resources\Messaging\Templates\Pages\ViewTemplate;
use App\Filament\Customer\Resources\Messaging\Templates\TemplateResource;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('customer can list their templates', function () {
    $user = User::factory()->create();
    $templates = Template::factory()->for($user)->count(3)->create();

    livewire(ListTemplates::class, ['resource' => TemplateResource::class])
        ->actingAs($user)
        ->assertCanSeeTableRecords($templates);
});

test('customer can view a template', function () {
    $user = User::factory()->create();
    $template = Template::factory()->for($user)->create();

    livewire(ViewTemplate::class, ['record' => $template->id, 'resource' => TemplateResource::class])
        ->actingAs($user)
        ->assertSee($template->name);
});

test('customer can create a template', function () {
    $user = User::factory()->create();

    livewire(CreateTemplate::class, ['resource' => TemplateResource::class])
        ->actingAs($user)
        ->fillForm(['name' => 'Test Template', 'body' => 'Hello world'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Template::where('user_id', $user->id)->where('name', 'Test Template')->exists())->toBeTrue();
});

test('customer can edit their template', function () {
    $user = User::factory()->create();
    $template = Template::factory()->for($user)->create(['name' => 'Original']);

    livewire(EditTemplate::class, ['record' => $template->id, 'resource' => TemplateResource::class])
        ->actingAs($user)
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($template->refresh()->name)->toBe('Updated');
});

test('customer cannot view templates of another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $template = Template::factory()->for($other)->create();

    livewire(ViewTemplate::class, ['record' => $template->id, 'resource' => TemplateResource::class])
        ->actingAs($user)
        ->assertForbidden();
});
