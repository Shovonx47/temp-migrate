<?php

namespace App\Http\Middleware;

use App\Enums\UserApproval;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        if ($user->approval !== UserApproval::Approved) {
            return $this->deny('Account is not approved for API usage.', 403);
        }

        return $next($request);
    }

    private function deny(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }
}
