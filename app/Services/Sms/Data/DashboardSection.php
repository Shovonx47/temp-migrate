<?php

namespace App\Services\Sms\Data;

final readonly class DashboardSection
{
    public function __construct(
        public string $title,
        public array $components = [],
    ) {}
}
