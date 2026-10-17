<?php

namespace App\Filament\Admin\Resources\Users\Sps\Actions;

use STS\FilamentImpersonate\Actions\Impersonate;

class LoginAction
{
    public static function make(): Impersonate
    {
        return Impersonate::make()
            ->guard('sp')
            ->redirectTo('/sp')
            ->modalHeading('Login as S P')
            ->modalDescription('This action grants full access to this S P account and is intended strictly for authorized debugging or support purposes.');
    }
}
