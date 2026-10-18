<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugAuthSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $before = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'session_id' => session()->getId(),
            'superman_id' => auth()->guard('superman')->id(),
            'customer_id' => auth()->guard('customer')->id(),
            'customer_session' => session()->get(
                auth()->guard('customer')->getName()
            ),
            'superman_session' => session()->get(
                auth()->guard('superman')->getName()
            ),
            'impersonated_by' => session()->get('impersonated_by'),
        ];

        Log::info('AUTH_SESSION_BEFORE', $before);

        $response = $next($request);

        Log::info('AUTH_SESSION_AFTER', [
            'session_has_impersonated_by' => session()->has('impersonated_by'),
            'session_has_impersonator_guard' => session()->has('impersonator_guard'),
            'session_has_impersonator_guard_using' => session()->has('impersonator_guard_using'),
            'url' => $request->fullUrl(),
            'session_id' => session()->getId(),
            'superman_id' => auth()->guard('superman')->id(),
            'customer_id' => auth()->guard('customer')->id(),
            'customer_session' => session()->get(
                auth()->guard('customer')->getName()
            ),
            'superman_session' => session()->get(
                auth()->guard('superman')->getName()
            ),
            'impersonated_by' => session()->get('impersonated_by'),
            'status' => $response->getStatusCode(),
            'location' => $response->headers->get('Location'),
        ]);

        return $response;
    }
}
