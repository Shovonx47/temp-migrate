<?php

namespace App\Filament\Actions;

use App\Models\TariffPlan;
use App\Services\TariffSlugService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;

class CopySignupLinkAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'copySignupLink';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Copy Signup Link')
            ->icon(Heroicon::OutlinedLink)
            ->color('info')
            ->disabled(fn (TariffPlan $record): bool => ! $record->status?->isActive() || blank($record->slug))
            ->tooltip(fn (TariffPlan $record): ?string => $record->status?->isActive()
                ? null
                : 'This plan is not Active, so its signup link will not work.')
            // The copy runs in the browser on the click itself (browsers only allow clipboard
            // access from a real user gesture). Needs HTTPS or localhost.
            ->extraAttributes(fn (TariffPlan $record): array => [
                'x-on:click' => 'window.navigator.clipboard?.writeText('
                    .Js::from(TariffSlugService::signupUrl($record))->toHtml().')',
            ])
            ->action(function (): void {
                Notification::make()
                    ->title('Signup link copied successfully.')
                    ->success()
                    ->send();
            });
    }
}
