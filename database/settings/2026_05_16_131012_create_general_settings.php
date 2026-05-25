<?php

use Spatie\LaravelSettings\Exceptions\SettingAlreadyExists;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateGeneralSettings extends SettingsMigration
{
    /**
     * @throws SettingAlreadyExists
     */
    public function up(): void
    {
        $this->migrator->add('general.appName', 'Sendora');
        $this->migrator->add('general.logoUrl', 'logo.png');
    }
}
