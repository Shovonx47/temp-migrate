<?php

namespace App\Filament\Admin\Resources\Users\Sps\Actions;

use Filament\Facades\Filament;
use STS\FilamentImpersonate\Actions\Impersonate;

class LoginAction
{
    public static function make()
    {
        return Impersonate::make()
            ->label('Login S P')
            ->guard('sp')
            ->redirectTo(fn () => Filament::getPanel('sp')->getUrl())
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Login as S P')
            ->modalDescription('This action grants full access to this S P account and is intended strictly for authorized debugging or support purposes. Never use it to impersonate a user without explicit permission.');
    }
}
