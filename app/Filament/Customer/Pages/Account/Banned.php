<?php

namespace App\Filament\Customer\Pages\Account;

use Filament\Panel;

final class Banned extends Page
{
    public static function getSlug(?Panel $panel = null): string
    {
        return 'account/banned';
    }

    public function getTitle(): string
    {
        return 'Account Banned';
    }

    public function getHeading(): string
    {
        return 'Your account has been banned';
    }

    public function getSubheading(): string
    {
        return 'Your account has been banned due to violations of our Terms & Conditions. If you believe this is a mistake, please contact support for further assistance.';
    }
}
