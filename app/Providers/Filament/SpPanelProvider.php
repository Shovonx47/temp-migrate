<?php

namespace App\Providers\Filament;

use App\Filament\Sp\Pages\Dashboard;
use App\Settings\GeneralSettings;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SpPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $generalSettings = app(GeneralSettings::class);

        return $panel
            ->id('sp')
            ->path('sp')
            ->viteTheme('resources/css/app.css')
            ->login()
            ->passwordReset()
            ->profile()
            ->authGuard('sp')
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ])
            ->databaseTransactions()
            ->databaseNotifications()
            ->lazyLoadedDatabaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->brandName(fn () => $generalSettings->appName)
            ->brandLogo(fn () => $generalSettings->getLogoUrl())
            ->brandLogoHeight('50px')
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->sidebarWidth('300px')
            ->maxContentWidth(Width::Full)
            ->colors(['primary' => Color::Amber])
            ->discoverResources(
                in: app_path('Filament/Sp/Resources'),
                for: 'App\\Filament\\Sp\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Sp/Pages'),
                for: 'App\\Filament\\Sp\\Pages',
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Sp/Widgets'),
                for: 'App\\Filament\\Sp\\Widgets',
            )
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                name: PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                hook: fn () => Blade::render("@livewire('balance-button')"),
            )
            ->spa()
            ->unsavedChangesAlerts();
    }
}
