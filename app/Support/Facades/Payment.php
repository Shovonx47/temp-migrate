<?php

namespace App\Support\Facades;

use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see PaymentManager
 */
class Payment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentManager::class;
    }
}
