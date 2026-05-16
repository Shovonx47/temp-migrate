<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public bool $topNavigation;

    public static function group(): string
    {
        return 'general';
    }
}
