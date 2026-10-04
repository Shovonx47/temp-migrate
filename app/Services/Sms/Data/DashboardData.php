<?php

namespace App\Services\Sms\Data;

final readonly class DashboardData
{
    public function __construct(
        public array $cards = [],
        public array $sections = [],
    ) {}
}
