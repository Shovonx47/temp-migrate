<?php

namespace App\Contracts;

use App\Services\Payment\Data\PaymentRequest;
use App\Services\Payment\Data\PaymentResponse;

interface PaymentGateway
{
    public function create(PaymentRequest $request): PaymentResponse;

    public function verify(array $payload): PaymentResponse;

    public function handleWebhook(array $payload): PaymentResponse;
}
