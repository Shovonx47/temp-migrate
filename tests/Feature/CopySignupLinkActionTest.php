<?php

use App\Enums\TariffPlanStatus;
use App\Filament\Actions\CopySignupLinkAction;
use App\Models\TariffPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders x-on:click with signup URL for an active plan', function () {
    $plan = TariffPlan::create([
        'name' => 'Gold Plan',
        'slug' => 'gold-plan',
        'status' => TariffPlanStatus::Active,
    ]);

    $action = CopySignupLinkAction::make()->record($plan);

    expect($action->isDisabled())->toBeFalse();
    expect($action->getAlpineClickHandler())->toContain('gold-plan');
    expect($action->toHtml())->toContain('x-on:click=');
    expect($action->toHtml())->toContain('gold-plan');
    expect($action->toHtml())->not->toContain('wire:click');
});

it('disables the copy action when plan is not active', function (TariffPlanStatus $status) {
    $plan = TariffPlan::create([
        'name' => 'Inactive Plan',
        'slug' => 'inactive-plan',
        'status' => $status,
    ]);

    $action = CopySignupLinkAction::make()->record($plan);

    expect($action->isDisabled())->toBeTrue();
    expect($action->getTooltip())->toBe('This plan is not Active, so its signup link will not work.');
    expect($action->toHtml())->toContain('fi-disabled');
})->with([TariffPlanStatus::Draft, TariffPlanStatus::Archived]);

it('disables the copy action when plan has no slug', function () {
    $plan = new TariffPlan([
        'name' => 'No Slug Plan',
        'status' => TariffPlanStatus::Active,
    ]);
    $plan->slug = null;

    $action = CopySignupLinkAction::make()->record($plan);

    expect($action->isDisabled())->toBeTrue();
    expect($action->getTooltip())->toBe('This plan does not have a signup slug.');
});
