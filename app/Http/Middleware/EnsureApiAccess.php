<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccess
{
    /**
     * Handle an incoming request.
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

        $approvedIps = $user->ipWhitelistRequests()
            ->approved()
            ->pluck('ip_address')
            ->toArray();

        if (! empty($approvedIps)) {
            $clientIp = $request->ip();

            if (! $clientIp || ! IpUtils::checkIp($clientIp, $approvedIps)) {
                return $this->deny('Your IP address is not whitelisted.', 403);
            }
        }

        return $next($request);
    }

    private function deny(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }
}
