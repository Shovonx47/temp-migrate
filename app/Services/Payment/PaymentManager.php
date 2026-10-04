<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Services\Payment\Drivers\LogDriver;
use App\Services\Payment\Drivers\PipraPayDriver;
use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('payment.default');
    }

    protected function createPiprapayDriver(): PaymentGateway
    {
        return new PipraPayDriver($this->config->get('payment.drivers.piprapay'));
    }

    protected function createLogDriver(): PaymentGateway
    {
        return new LogDriver($this->config->get('payment.drivers.log', []));
    }
}
