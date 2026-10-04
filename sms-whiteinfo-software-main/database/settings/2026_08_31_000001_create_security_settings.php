<?php

use Spatie\LaravelSettings\Exceptions\SettingAlreadyExists;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateSecuritySettings extends SettingsMigration
{
    /**
     * @throws SettingAlreadyExists
     */
    public function up(): void
    {
        $this->migrator->add('security.ip_whitelist_enforced', true);
        $this->migrator->add('security.allow_all_when_whitelist_empty', false);
        $this->migrator->add('security.auto_approve_api_requests', false);
        $this->migrator->add('security.web_portal_bypass_enabled', true);
        $this->migrator->add('security.trusted_portal_ips', "127.0.0.1\n::1");
        $this->migrator->add('security.blocked_message', 'Your IP address is not whitelisted. SMS sending through this IP is not allowed.');
    }
}
