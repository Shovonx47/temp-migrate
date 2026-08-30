<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use App\Settings\SecuritySettings;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccess
{
    /**
     * Handle an incoming API request.
     *
     * Enforces account approval, API access rights, and the dynamic
     * IP Whitelist policy (Web Portal bypass vs Direct API enforcement).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var ?User $user */
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->deny('Unauthenticated.', 401);
        }

        if (! $user->api_access) {
            return $this->deny('API access is disabled for this account.', 403);
        }

        if ($user->status !== UserStatus::Approved) {
            return $this->deny('Account is not approved for API usage.', 403);
        }

        // Exemption: Allow customers to reach the IP Whitelist management endpoint
        // so they can register their initial public IP via API without being blocked.
        if ($request->is('api/v1/ip-whitelists*')) {
            return $next($request);
        }

        /** @var SecuritySettings $settings */
        $settings = app(SecuritySettings::class);
        $clientIp = $request->ip();

        // ─── 1. Web Portal Backend Bypass ─────────────────────────────────────
        // When using the Customer Web Portal, the request travels:
        // Customer Browser → Web Portal Backend → API Server.
        // In this case, the source IP is the backend server itself, so end-customer
        // IP whitelisting is not required if the portal bypass is enabled.
        if ($settings->web_portal_bypass_enabled && $clientIp) {
            $trustedIps = array_filter(
                array_map('trim', preg_split('/[\r\n,]+/', $settings->trusted_portal_ips))
            );

            if (! empty($trustedIps) && IpUtils::checkIp($clientIp, $trustedIps)) {
                return $next($request);
            }
        }

        // ─── 2. Direct API Whitelist Enforcement ──────────────────────────────
        // If IP whitelisting is disabled in Admin settings, allow all requests.
        if (! $settings->ip_whitelist_enforced) {
            return $next($request);
        }

        $approvedIps = $user->ipWhitelistRequests()
            ->approved()
            ->pluck('ip_address')
            ->toArray();

        // If the customer has NO approved whitelisted IPs:
        if (empty($approvedIps)) {
            if ($settings->allow_all_when_whitelist_empty) {
                // Permissive mode: allow until an IP is configured
                return $next($request);
            }

            // Strict mode (Default): block until at least one IP is approved
            return $this->deny(
                'Direct API access and SMS sending requires at least one approved public IP address in your whitelist. Please add your IP in Developer Settings or via the API.',
                403,
                ['client_ip' => $clientIp]
            );
        }

        // If the customer has approved IPs: request IP must match one of them
        if (! $clientIp || ! IpUtils::checkIp($clientIp, $approvedIps)) {
            $blockedMessage = ! empty($settings->blocked_message)
                ? $settings->blocked_message
                : 'Your IP address is not whitelisted. SMS sending through this IP is not allowed.';

            return $this->deny($blockedMessage, 403, [
                'client_ip' => $clientIp,
            ]);
        }

        return $next($request);
    }

    private function deny(string $message, int $status, array $extra = []): JsonResponse
    {
        return new JsonResponse(array_merge(['message' => $message], $extra), $status);
    }
}
