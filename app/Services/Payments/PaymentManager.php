<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Services\Payments\Drivers\LogDriver;
use App\Services\Payments\Drivers\PipraPayDriver;
use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('payments.default');
    }

    protected function createPiprapayDriver(): PaymentGateway
    {
        return new PipraPayDriver($this->config->get('payments.drivers.piprapay'));
    }

    protected function createLogDriver(): PaymentGateway
    {
        return new LogDriver($this->config->get('payments.drivers.log', []));
    }
}
