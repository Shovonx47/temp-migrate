<?php

namespace App\Filament\Actions;

use App\Models\TariffPlan;
use App\Services\TariffSlugService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class OpenSignupLinkAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'openSignupLink';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Open Signup Link')
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray')
            ->url(fn (TariffPlan $record): string => TariffSlugService::signupUrl($record), shouldOpenInNewTab: true)
            ->disabled(fn (TariffPlan $record): bool => ! $record->status?->isActive() || blank($record->slug));
    }
}
