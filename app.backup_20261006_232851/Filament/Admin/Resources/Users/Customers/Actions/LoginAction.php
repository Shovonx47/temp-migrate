<?php

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use STS\FilamentImpersonate\Actions\Impersonate;

class LoginAction
{
    public static function make(): Impersonate
    {
        return Impersonate::make()
            ->guard('customer')
            ->redirectTo('/')
            ->modalHeading('Login as Customer')
            ->modalDescription('This action grants full access to another user account and is intended strictly for authorized debugging or support purposes.');
    }
}
