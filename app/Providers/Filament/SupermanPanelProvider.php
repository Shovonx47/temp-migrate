<?php

namespace App\Providers\Filament;

use App\Settings\GeneralSettings;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Throwable;

class SupermanPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Settings query can fail before migrations run (e.g. fresh test DBs);
        // fall back to defaults so panel boot does not break the test suite.
        try {
            $generalSettings = app(GeneralSettings::class);
            $topNavigation = (bool) $generalSettings->topNavigation;
        } catch (Throwable) {
            $topNavigation = false;
        }

        return $panel
            // Identity
            ->default()
            ->id('superman')
            ->path('superman')
            ->viteTheme('resources/css/app.css')

            // Authentication
            ->login()
            ->authGuard('superman')
            ->multiFactorAuthentication([
                AppAuthentication::make(),
            ])

            // Layout
            ->colors(['primary' => Color::Emerald])
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->sidebarWidth('300px')
            ->maxContentWidth(Width::Full)
            ->topNavigation($topNavigation)

            // Database
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling(30)

            // Discovery
            ->discoverResources(
                in: app_path('Filament/Admin/Resources'),
                for: 'App\\Filament\\Admin\\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Admin/Pages'),
                for: 'App\\Filament\\Admin\\Pages',
            )
            ->pages([
                Dashboard::class,
            ])

            // Widgets
            ->discoverWidgets(
                in: app_path('Filament/Admin/Widgets'),
                for: 'App\\Filament\\Admin\\Widgets',
            )
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])

            // Middleware
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

            ->plugins([
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local') && request()->ip() === '127.0.0.1')
                    ->users([
                        'Superman' => 'superman@example.com',
                    ]),
            ])
            ->spa()
            ->unsavedChangesAlerts();
    }
}
