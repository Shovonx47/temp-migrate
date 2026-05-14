<?php

namespace App\Contracts;

use App\Models\Deposit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * Initiate a payment and return a redirect to the hosted checkout page.
     */
    public function initiate(Deposit $deposit): RedirectResponse;

    /**
     * Verify a payment by its PipaPay transaction ID (pp_id).
     * Returns the normalized payment data or throws on failure.
     */
    public function verify(string $ppId): array;

    /**
     * Handle an incoming webhook from the gateway.
     * Returns the normalized payload on success.
     */
    public function handleWebhook(Request $request): array;

    /**
     * Process a refund for the given pp_id.
     * Returns the normalized refund response.
     */
    public function refund(string $ppId): array;
}
