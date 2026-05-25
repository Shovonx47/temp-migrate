<?php

namespace App\Filament\Admin\Resources\Users\Customers\Actions;

use App\Models\User;
use STS\FilamentImpersonate\Actions\Impersonate;

class LoginAction
{
    public static function make()
    {
        return Impersonate::make()
            ->label('Login Customer')
            ->guard(fn (User $record) => $record->type->value)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Login as Customer')
            ->modalDescription('This action grants full access to another user account and is intended strictly for authorized debugging or support purposes. Never use it to impersonate a user without explicit permission.');
    }
}
