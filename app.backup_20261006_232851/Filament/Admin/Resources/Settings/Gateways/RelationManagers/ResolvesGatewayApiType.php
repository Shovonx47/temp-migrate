<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\RelationManagers;

use App\Models\Gateway;
use Livewire\Attributes\On;

trait ResolvesGatewayApiType
{
    public ?string $liveApiType = null;

    #[On('gateway-api-type-changed')]
    public function onGatewayApiTypeChanged(?string $apiType): void
    {
        $this->liveApiType = $this->normalizeGatewayApiType($apiType);
    }

    protected function gatewayApiType(): string
    {
        if (filled($this->liveApiType)) {
            return $this->normalizeGatewayApiType($this->liveApiType);
        }

        $owner = $this->getOwnerRecord();

        if ($owner instanceof Gateway) {
            return $owner->smsApiType();
        }

        return 'iptsp';
    }

    protected function gatewayUsesMasking(): bool
    {
        return $this->gatewayApiType() === 'mno';
    }

    protected function operatorRateColumnLabel(): string
    {
        return $this->gatewayApiType() === 'mno'
            ? 'MNO Operator Rate'
            : 'IPTSP Operator Rate';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyMaskingToFormData(array $data, string $maskingKey): array
    {
        if (! $this->gatewayUsesMasking()) {
            $data[$maskingKey] = null;
        }

        return $data;
    }

    protected function normalizeGatewayApiType(?string $apiType): string
    {
        return strtolower(trim((string) $apiType)) === 'mno' ? 'mno' : 'iptsp';
    }
}
