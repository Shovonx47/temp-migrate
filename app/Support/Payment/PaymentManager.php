<?php

namespace App\Support\Payment;

use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('payment.default');
    }
}
