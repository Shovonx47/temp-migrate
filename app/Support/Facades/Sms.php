<?php

namespace App\Support\Facades;

use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @see SmsManager
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}
