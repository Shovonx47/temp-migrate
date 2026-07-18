<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Enums\GatewayDriver;
use App\Models\Gateway;
use App\Services\Sms\Drivers\AnbernetDriver;
use App\Services\Sms\Drivers\InfozillionDriver;
use App\Services\Sms\Drivers\LogDriver;
use App\Services\Sms\Drivers\QSmsDriver;
use App\Services\Sms\Drivers\ReveSmsDriver;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * @method SmsGateway driver(string $driver = null)
 */
class SmsManager extends Manager
{
    private ?Gateway $gateway = null;

    /**
     * Set the Gateway model to use for driver credentials.
     * Clears the cached driver instance so the next driver() call
     * picks up the new gateway's credentials.
     */
    public function usingGateway(Gateway $gateway): static
    {
        $this->gateway = $gateway;
        unset($this->drivers[$gateway->driver->value]);

        return $this;
    }

    public function getDefaultDriver(): string
    {
        return $this->config->get('sms.default', GatewayDriver::Log->value);
    }

    protected function createLogDriver(): SmsGateway
    {
        return new LogDriver(
            config: $this->gateway?->config ?? [],
        );
    }

    protected function createRevesmsDriver(): SmsGateway
    {
        if (empty($this->gateway?->credentials['base_url'])) {
            throw new InvalidArgumentException('ReveSMS requires credentials: base_url, api_key, secret_key.');
        }

        return new ReveSmsDriver(
            credentials: $this->gateway->credentials,
        );
    }

    protected function createQsmsDriver(): SmsGateway
    {
        if (empty($this->gateway?->credentials['base_url'])) {
            throw new InvalidArgumentException('QSMS requires credentials: base_url, api_key, client_id.');
        }

        return new QSmsDriver(
            credentials: $this->gateway->credentials,
        );
    }

    protected function createAnbernetDriver(): SmsGateway
    {
        if (empty($this->gateway?->credentials['base_url'])) {
            throw new InvalidArgumentException('Anbernet requires credentials: base_url, account, password, token.');
        }

        return new AnbernetDriver(
            credentials: $this->gateway->credentials,
        );
    }

    protected function createInfozillionDriver(): SmsGateway
    {
        if (empty($this->gateway?->credentials['base_url'])) {
            throw new InvalidArgumentException('Infozillion requires credentials: base_url, username, password, bill_msisdn, api_key.');
        }

        return new InfozillionDriver(
            credentials: $this->gateway->credentials,
        );
    }
}
