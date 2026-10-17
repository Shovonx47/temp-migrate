<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SmsSettings extends Settings
{
    /**
     * Master SMS toggle - global kill-switch for sending.
     */
    public bool $sms_enabled = true;

    /**
     * Default SMS type: plain_text, masking, non_masking, promotional, transactional.
     */
    public string $default_sms_type = 'plain_text';

    /**
     * Default character encoding: GSM-7, UCS-2, UTF-8.
     */
    public string $default_encoding = 'GSM-7';

    /**
     * Unicode detection & handling.
     */
    public bool $auto_detect_unicode = true;

    public bool $force_unicode = false;

    public bool $strip_incompatible_chars = false;

    /**
     * Long SMS concatenation & part limits.
     */
    public bool $allow_long_sms = true;

    public int $max_sms_parts = 10;

    /**
     * Duplicate SMS protection.
     */
    public bool $duplicate_protection_enabled = false;

    public int $duplicate_window_seconds = 120;

    /**
     * Phone Number Validation.
     * Modes: bangladesh_strict, international_e164
     */
    public string $validation_mode = 'bangladesh_strict';

    public string $default_country_code = '+880';

    /**
     * Default Sender ID / CLI fallback if none is specified.
     */
    public ?string $default_sender_id = null;

    /**
     * SMS Logging configuration.
     * Log level: debug, info, warning, error
     */
    public string $log_level = 'info';

    public bool $log_payloads = true;

    public int $log_retention_days = 30;

    /**
     * Message ID Configuration.
     * Formats: prefix_timestamp_random, uuid, numeric
     */
    public string $message_id_prefix = 'SMS';

    public string $message_id_format = 'prefix_timestamp_random';

    public static function group(): string
    {
        return 'sms';
    }
}
