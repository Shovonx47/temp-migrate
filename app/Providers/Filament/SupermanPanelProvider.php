<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard;
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

class SupermanPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $generalSettings = app(GeneralSettings::class);

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

            // Branding — resolved lazily so service providers do not query the DB at boot time.
            ->brandName(fn () => $generalSettings->appName)
            ->brandLogo(fn () => $generalSettings->getLogoUrl())
            ->brandLogoHeight('50px')

            // Database
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')

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
            ->widgets([])

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
            ->renderHook(
                name: PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                hook: fn () => Blade::render("@livewire('server-actions')")
            )
            ->plugins($this->plugins())
            ->spa()
            ->unsavedChangesAlerts();
    }

    /**
     * Conditionally register panel plugins.
     *
     * Plugins are only added if their package class is installed,
     * allowing dev-only packages to be safely omitted in production.
     */
    protected function plugins(): array
    {
        $plugins = [];

        $developerLogins = 'DutchCodingCompany\\FilamentDeveloperLogins\\FilamentDeveloperLoginsPlugin';

        if (class_exists($developerLogins)) {
            $plugins[] = $developerLogins::make()
                ->enabled(app()->environment('local') && request()->host() === 'sendora.test')
                ->users([
                    'Superman' => 'superman@example.com',
                ]);
        }

        return $plugins;
    }
}
