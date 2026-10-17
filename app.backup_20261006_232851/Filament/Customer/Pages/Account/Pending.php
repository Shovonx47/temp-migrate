<?php

namespace App\Filament\Customer\Pages\Account;

use Filament\Panel;

final class Pending extends Page
{
    public static function getSlug(?Panel $panel = null): string
    {
        return 'account/pending';
    }

    public function getTitle(): string
    {
        return 'Account Pending Approval';
    }

    public function getHeading(): string
    {
        return 'Your account is pending approval';
    }

    public function getSubheading(): string
    {
        return 'Thank you for registering. Your account is currently under review. We will notify you once it has been approved.';
    }
}
