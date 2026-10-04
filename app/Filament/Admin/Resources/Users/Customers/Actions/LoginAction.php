<?php

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use Filament\Facades\Filament;
use STS\FilamentImpersonate\Actions\Impersonate;

class LoginAction
{
    public static function make()
    {
        return Impersonate::make()
            ->label('Login Customer')
            ->guard('customer')
            ->redirectTo(fn () => Filament::getPanel('customer')->getUrl())
            ->backTo(fn () => Filament::getPanel('superman')->getUrl())
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Login as Customer')
            ->modalDescription('This action grants full access to another user account and is intended strictly for authorized debugging or support purposes. Never use it to impersonate a user without explicit permission.');
    }
}
