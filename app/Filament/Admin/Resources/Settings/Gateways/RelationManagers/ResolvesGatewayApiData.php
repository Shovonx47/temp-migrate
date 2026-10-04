<?php

namespace App\Filament\Admin\Resources\Settings\Gateways\RelationManagers;

/**
 * Backward-compatible alias for live servers that still `use ResolvesGatewayApiData`.
 */
trait ResolvesGatewayApiData
{
    use ResolvesGatewayApiType;
}
