<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\CustomerPanelProvider;
use App\Providers\Filament\SupermanPanelProvider;
use App\Providers\FilamentServiceProvider;

return [
    AppServiceProvider::class,
    FilamentServiceProvider::class,
    SupermanPanelProvider::class,
    CustomerPanelProvider::class,
];
