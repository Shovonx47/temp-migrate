<?php

namespace App\Services\Sms\Data;

final readonly class StatCard
{
    public function __construct(
        public string $label,
        public string $value,
        public ?string $description = null,
        public ?string $color = null,
        public ?string $icon = null,
    ) {}
}
