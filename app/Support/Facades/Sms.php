<?php

namespace App\Support\Facades;

use App\Contracts\SmsGateway;
use App\Models\Gateway;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see SmsManager
 *
 * @method static SmsGateway driver(string $driver = null)
 * @method static SmsManager usingGateway(Gateway $gateway)
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}
