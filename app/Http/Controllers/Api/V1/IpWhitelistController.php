<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\IpWhitelistStatus;
use App\Http\Controllers\Controller;
use App\Models\IpWhitelistAuditLog;
use App\Models\IpWhitelistRequest;
use App\Models\User;
use App\Settings\SecuritySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\BodyParam;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;
use Symfony\Component\HttpFoundation\IpUtils;

#[Group('IP Whitelist', 'Manage whitelisted public IP addresses for direct API access and SMS sending.')]
class IpWhitelistController extends Controller
{
    /**
     * List all IP whitelist entries for the authenticated customer.
     */
    #[Endpoint('List Whitelisted IPs', 'Retrieve all IP whitelist entries registered to your account, including active, pending, and disabled entries.')]
    #[Response(content: [
        'data' => [
            [
                'id'         => 1,
                'ip_address' => '203.0.113.195',
                'label'      => 'Production API Server',
                'status'     => 'approved',
                'created_at' => '2026-08-31T01:00:00.000000Z',
                'reviewed_at'=> '2026-08-31T01:05:00.000000Z',
            ],
        ],
    ], status: 200)]
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $ips = $user->ipWhitelistRequests()
            ->latest()
            ->get(['id', 'ip_address', 'label', 'status', 'admin_note', 'created_at', 'reviewed_at'])
            ->map(fn (IpWhitelistRequest $item) => [
                'id'          => $item->id,
                'ip_address'  => $item->ip_address,
                'label'       => $item->label,
                'status'      => $item->status instanceof IpWhitelistStatus ? $item->status->value : (string) $item->status,
                'admin_note'  => $item->admin_note,
                'created_at'  => $item->created_at?->toISOString(),
                'reviewed_at' => $item->reviewed_at?->toISOString(),
            ]);

        return new JsonResponse(['data' => $ips], 200);
    }

    /**
     * Submit a public IP address or CIDR range to be whitelisted for API access.
     */
    #[Endpoint('Add IP to Whitelist', 'Submit a public IP address (or CIDR block) for API access. Private, local, and reserved ranges are strictly rejected.')]
    #[BodyParam('ip_address', 'string', 'Public IPv4/IPv6 address or CIDR range.', required: true, example: '203.0.113.195')]
    #[BodyParam('label', 'string', 'Optional label to identify this server.', required: false, example: 'Primary Dispatch Server')]
    #[Response(content: [
        'message' => 'IP whitelist entry created successfully.',
        'data'    => [
            'id'         => 2,
            'ip_address' => '203.0.113.195',
            'label'      => 'Primary Dispatch Server',
            'status'     => 'pending',
            'created_at' => '2026-08-31T01:10:00.000000Z',
        ],
    ], status: 201)]
    #[Response(content: ['message' => 'Private or reserved IP addresses cannot be whitelisted.'], status: 422)]
    public function store(Request $request, SecuritySettings $settings): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rawIp = trim((string) $request->input('ip_address', ''));
        $label = trim((string) $request->input('label', ''));

        if (empty($rawIp)) {
            return new JsonResponse(['message' => 'The ip_address field is required.'], 422);
        }

        // Validate public IP / CIDR
        $validationError = $this->validatePublicIp($rawIp);
        if ($validationError !== null) {
            return new JsonResponse(['message' => $validationError], 422);
        }

        // Check for duplicates for this customer
        $existing = $user->ipWhitelistRequests()->where('ip_address', $rawIp)->first();
        if ($existing) {
            return new JsonResponse([
                'message' => "IP address {$rawIp} is already registered on your account with status [{$existing->status->value}].",
                'data'    => [
                    'id'         => $existing->id,
                    'ip_address' => $existing->ip_address,
                    'status'     => $existing->status->value,
                ],
            ], 409);
        }

        // Determine status based on settings (auto-approve vs pending)
        $autoApprove = $settings->auto_approve_api_requests;
        $status      = $autoApprove ? IpWhitelistStatus::Approved : IpWhitelistStatus::Pending;
        $reviewedAt  = $autoApprove ? now() : null;

        $entry = $user->ipWhitelistRequests()->create([
            'ip_address'  => $rawIp,
            'label'       => ! empty($label) ? $label : null,
            'status'      => $status,
            'reviewed_at' => $reviewedAt,
        ]);

        // Audit Log
        IpWhitelistAuditLog::record(
            userId: $user->id,
            action: 'created',
            ipAddress: $rawIp,
            requestId: $entry->id,
            oldValues: null,
            newValues: [
                'ip_address' => $entry->ip_address,
                'label'      => $entry->label,
                'status'     => $entry->status->value,
            ],
            note: $autoApprove ? 'Added via API (Auto-approved per security policy)' : 'Added via API (Pending review)',
            actor: $user,
        );

        return new JsonResponse([
            'message' => $autoApprove
                ? 'IP address whitelisted and approved successfully.'
                : 'IP whitelist request submitted successfully. Awaiting administrator review.',
            'data'    => [
                'id'         => $entry->id,
                'ip_address' => $entry->ip_address,
                'label'      => $entry->label,
                'status'     => $entry->status->value,
                'created_at' => $entry->created_at?->toISOString(),
            ],
        ], 201);
    }

    /**
     * Delete an IP whitelist entry.
     */
    #[Endpoint('Delete Whitelisted IP', 'Remove a whitelisted IP entry from your account. API requests originating from this IP will immediately be blocked.')]
    #[UrlParam('id', 'integer', 'The ID of the IP whitelist entry to remove.', required: true, example: 1)]
    #[Response(content: ['message' => 'IP whitelist entry removed successfully.'], status: 200)]
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $entry = $user->ipWhitelistRequests()->find($id);

        if (! $entry) {
            return new JsonResponse(['message' => 'IP whitelist entry not found.'], 404);
        }

        $ipAddress = $entry->ip_address;
        $status    = $entry->status?->value;

        $entry->delete();

        // Audit Log
        IpWhitelistAuditLog::record(
            userId: $user->id,
            action: 'deleted',
            ipAddress: $ipAddress,
            requestId: null,
            oldValues: [
                'ip_address' => $ipAddress,
                'status'     => $status,
            ],
            newValues: null,
            note: 'Deleted by customer via API',
            actor: $user,
        );

        return new JsonResponse(['message' => "IP whitelist entry for {$ipAddress} removed successfully."], 200);
    }

    /**
     * Helper to validate that an IP or CIDR is valid, public, and not private/reserved.
     */
    private function validatePublicIp(string $val): ?string
    {
        if (! str_contains($val, '/')) {
            if (! filter_var($val, FILTER_VALIDATE_IP)) {
                return 'The ip_address must be a valid IPv4 or IPv6 address.';
            }

            if (! filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return 'Private or reserved IP addresses (e.g. 192.168.x.x, 10.x.x.x, 172.16.x.x, 127.0.0.1) cannot be whitelisted. Only public internet-facing IPs are permitted.';
            }

            if (IpUtils::checkIp($val, ['100.64.0.0/10', '0.0.0.0/8'])) {
                return 'Carrier-grade NAT or reserved network addresses cannot be whitelisted.';
            }

            return null;
        }

        // CIDR block
        $parts = explode('/', $val, 2);
        if (count($parts) !== 2) {
            return 'Invalid CIDR format.';
        }

        [$ip, $mask] = $parts;
        if (! is_numeric($mask)) {
            return 'CIDR prefix length must be numeric.';
        }

        $mask   = (int) $mask;
        $isIpv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
        $isIpv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        if (! $isIpv4 && ! $isIpv6) {
            return 'The base IP address in the CIDR block must be a valid IP address.';
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'Private or reserved IP ranges (e.g. 192.168.x.x, 10.x.x.x, 172.16.x.x) cannot be whitelisted. Only public internet-facing IP blocks are permitted.';
        }

        if ($isIpv4 && ($mask < 16 || $mask > 32)) {
            return 'For IPv4 CIDR blocks, the subnet mask must be between /16 and /32.';
        }

        if ($isIpv6 && ($mask < 48 || $mask > 128)) {
            return 'For IPv6 CIDR blocks, the prefix length must be between /48 and /128.';
        }

        return null;
    }
}
