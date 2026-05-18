<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $appName = 'Sendora';
    public string $logoUrl = 'logo.png';

    public static function group(): string
    {
        return 'general';
    }
}
