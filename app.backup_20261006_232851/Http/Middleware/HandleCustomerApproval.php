<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Filament\Customer\Pages\Dashboard;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCustomerApproval
{
    /**
     * Routes that are always accessible regardless of approval status.
     */
    private const array ALWAYS_ALLOWED_ROUTES = [
        'filament.customer.account.kyc',
        'filament.customer.auth.logout',
        'filament.customer.auth.email-verification.*',
    ];

    /**
     * Status pages that should not be accessible to approved users.
     */
    private const array STATUS_ROUTES = [
        'filament.customer.account.pending',
        'filament.customer.account.rejected',
        'filament.customer.account.banned',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return $next($request);
        }

        // If customer was created by the Superman admin, do not trap them on "account.pending"
        if (method_exists($user, 'isCreatedByAdmin') && $user->isCreatedByAdmin() && $user->status === UserStatus::Pending) {
            return $next($request);
        }

        $redirectRoute = match ($user->status) {
            UserStatus::Pending => 'filament.customer.account.pending',
            UserStatus::Rejected => 'filament.customer.account.rejected',
            UserStatus::Banned => 'filament.customer.account.banned',
            default => null,
        };

        if ($redirectRoute !== null) {
            $allowedRoutes = [...self::ALWAYS_ALLOWED_ROUTES, $redirectRoute];

            if (! $request->routeIs($allowedRoutes)) {
                return to_route($redirectRoute);
            }

            return $next($request);
        }

        // Approved user: block access to status pages
        if ($request->routeIs(self::STATUS_ROUTES)) {
            return redirect()->to(Dashboard::getUrl(panel: 'customer'));
        }

        return $next($request);
    }
}
