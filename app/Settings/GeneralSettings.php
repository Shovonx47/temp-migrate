<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $appName = 'SMS Whiteinfo';

    public string $logoUrl = 'logo.png';

    public static function group(): string
    {
        return 'general';
    }

    public function getLogoUrl(): string
    {
        if (file_exists(storage_path('app/public/'.$this->logoUrl))) {
            return asset('storage/'.$this->logoUrl);
        }

        return asset($this->logoUrl);
    }
}
