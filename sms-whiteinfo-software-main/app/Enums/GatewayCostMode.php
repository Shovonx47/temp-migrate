<?php

namespace App\Enums;

enum GatewayCostMode: string
{
    case Manual = 'manual';
    case ApiAssisted = 'api_assisted';
}
