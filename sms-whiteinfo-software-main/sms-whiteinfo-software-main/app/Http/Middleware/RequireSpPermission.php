<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SpPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireSpPermission
{
    public function __construct(
        protected SpPermissionService $permissionService,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $guard = null): Response
    {
        $user = $guard ? $request->user($guard) : ($request->user('sp') ?? $request->user('superman') ?? $request->user());

        if (! $this->permissionService->can($user, $permission)) {
            abort(403, "You do not have the required [{$permission}] permission.");
        }

        return $next($request);
    }
}
