<?php

namespace App\Services\Sms\Data;

use App\Enums\DeliveryOutcome;
use Illuminate\Support\Carbon;

readonly class DeliveryStatusResponse
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public DeliveryOutcome $outcome,
        public ?string $rawStatus = null,
        public ?Carbon $deliveredAt = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}
}
