<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    /**
     * Whether IP whitelisting is enforced for direct API requests (SMS sending, etc.).
     */
    public bool $ip_whitelist_enforced = true;

    /**
     * When a customer has 0 approved whitelisted IPs:
     * - false: Direct API access is strictly BLOCKED (must have at least 1 approved IP).
     * - true: Permissive mode (all IPs allowed until customer adds an IP).
     */
    public bool $allow_all_when_whitelist_empty = false;

    /**
     * When customer requests an IP via API (curl/POST), should it be auto-approved?
     */
    public bool $auto_approve_api_requests = false;

    /**
     * Whether Web Portal Backend requests bypass IP whitelisting.
     */
    public bool $web_portal_bypass_enabled = true;

    /**
     * Comma-separated or newline-separated list of trusted Web Portal backend server IPs.
     */
    public string $trusted_portal_ips = "127.0.0.1\n::1";

    /**
     * Custom message returned when a request is blocked due to non-whitelisted IP.
     */
    public string $blocked_message = 'Your IP address is not whitelisted. SMS sending through this IP is not allowed.';

    public static function group(): string
    {
        return 'security';
    }
}
