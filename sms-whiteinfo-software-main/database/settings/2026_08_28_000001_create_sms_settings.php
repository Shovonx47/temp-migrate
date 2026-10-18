<?php

use Spatie\LaravelSettings\Exceptions\SettingAlreadyExists;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateSmsSettings extends SettingsMigration
{
    /**
     * @throws SettingAlreadyExists
     */
    public function up(): void
    {
        $this->migrator->add('sms.sms_enabled', true);
        $this->migrator->add('sms.default_sms_type', 'plain_text');
        $this->migrator->add('sms.default_encoding', 'GSM-7');
        $this->migrator->add('sms.auto_detect_unicode', true);
        $this->migrator->add('sms.force_unicode', false);
        $this->migrator->add('sms.strip_incompatible_chars', false);
        $this->migrator->add('sms.allow_long_sms', true);
        $this->migrator->add('sms.max_sms_parts', 10);
        $this->migrator->add('sms.duplicate_protection_enabled', false);
        $this->migrator->add('sms.duplicate_window_seconds', 120);
        $this->migrator->add('sms.validation_mode', 'bangladesh_strict');
        $this->migrator->add('sms.default_country_code', '+880');
        $this->migrator->add('sms.default_sender_id', null);
        $this->migrator->add('sms.log_level', 'info');
        $this->migrator->add('sms.log_payloads', true);
        $this->migrator->add('sms.log_retention_days', 30);
        $this->migrator->add('sms.message_id_prefix', 'SMS');
        $this->migrator->add('sms.message_id_format', 'prefix_timestamp_random');
    }
}
