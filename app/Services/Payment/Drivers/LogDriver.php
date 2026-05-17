<?php

namespace App\Services\Payment\Drivers;

use App\Contracts\PaymentGateway;
use App\Models\Deposit;
use App\Services\Payment\PaymentCheckoutResult;

final class LogDriver implements PaymentGateway
{
    public function create(Deposit $deposit): PaymentCheckoutResult
    {
        return PaymentCheckoutResult::success('trx-'.uniqid(), 'https://example.com/receipt');
    }
}
