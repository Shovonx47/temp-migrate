<?php

namespace App\Support\Facades;

use App\Contracts\PaymentGateway;
use App\Services\Payment\Data\PaymentRequest;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see PaymentManager
 *
 * @method static PaymentGateway driver(string $name)
 * @method static PaymentGateway initiate(PaymentRequest $request)
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}
