<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\CustomerPanelProvider;
use App\Providers\Filament\SpPanelProvider;
use App\Providers\Filament\SupermanPanelProvider;
use App\Providers\FilamentServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    FilamentServiceProvider::class,
    CustomerPanelProvider::class,
    SpPanelProvider::class,
    SupermanPanelProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
];
