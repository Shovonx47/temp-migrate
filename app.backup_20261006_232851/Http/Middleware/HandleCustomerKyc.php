<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\KycStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCustomerKyc
{
    /**
     * Handle an incoming request.
     *
     * Routes the customer to the KYC page if:
     *   - Their KYC is Pending (never submitted)
     *   - Their KYC requires resubmission (kyc_status = resubmit_required)
     *
     * Customers with UnderReview, Verified, or Rejected status are not redirected
     * so they can access the dashboard normally (admins handle rejections separately).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if ($customer && $this->requiresKycSubmission($customer) && ! $request->routeIs('filament.customer.account.kyc')) {
            return to_route('filament.customer.account.kyc');
        }

        return $next($request);
    }

    private function requiresKycSubmission($customer): bool
    {
        // Customers created by the Superman admin are pre-vetted and not held off for KYC verification.
        // They can voluntarily submit their KYC documents later at /account/kyc.
        if (method_exists($customer, 'isCreatedByAdmin') && $customer->isCreatedByAdmin()) {
            return false;
        }

        $status = $customer->kyc_status;

        // Redirect if documents have never been submitted (no front_nid)
        if (! filled($customer->front_nid)) {
            return true;
        }

        // Redirect if admin requested resubmission
        if ($status === KycStatus::ResubmitRequired) {
            return true;
        }

        return false;
    }
}
