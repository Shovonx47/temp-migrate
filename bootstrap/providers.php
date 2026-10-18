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
    // CustomerPanelProvider::class, // Customer portal migrated to Svelte SPA
    // SpPanelProvider::class, // SP reseller portal migrated to Svelte SPA
    // SupermanPanelProvider::class, // Superman admin panel migrated to Svelte SPA
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
];
