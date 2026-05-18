<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCustomerKyc
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if ($customer && ! $this->hasCompletedKyc($customer) && ! $request->routeIs('filament.customer.account.kyc')) {
            return to_route('filament.customer.account.kyc');
        }

        return $next($request);
    }

    private function hasCompletedKyc($customer): bool
    {
        return filled($customer->front_nid) && filled($customer->back_nid);
    }
}
