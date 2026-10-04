<?php

declare(strict_types=1);

namespace App\Filament\Sp\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;

class Login extends BaseLogin
{
    protected function getRememberFormComponent(): Component
    {
        return parent::getRememberFormComponent()->default(true);
    }

    public function authenticate(): ?LoginResponse
    {
        $this->data['remember'] = true;

        return parent::authenticate();
    }
}
