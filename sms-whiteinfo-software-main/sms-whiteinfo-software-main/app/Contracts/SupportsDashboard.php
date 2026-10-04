<?php

namespace App\Contracts;

use App\Services\Sms\Data\DashboardData;

interface SupportsDashboard
{
    public function getDashboardData(): DashboardData;
}
