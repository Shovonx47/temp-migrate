<?php

namespace App\Providers\Filament;

use App\Enums\CustomerNavigationGroup;
use App\Filament\Customer\Pages\Account\Banned;
use App\Filament\Customer\Pages\Account\Kyc;
use App\Filament\Customer\Pages\Account\Pending;
use App\Filament\Customer\Pages\Account\Rejected;
use App\Filament\Customer\Pages\Dashboard;
use App\Filament\Customer\Pages\Register;
use App\Http\Middleware\HandleCustomerApproval;
use App\Http\Middleware\HandleCustomerKyc;
use App\Settings\GeneralSettings;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class CustomerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $generalSettings = app(GeneralSettings::class);

        return $panel
            // Identify
            ->id('customer')
            ->path('/')
            ->viteTheme('resources/css/app.css')

            // Authentication
            ->login()
            ->registration(Register::class)
            ->passwordReset()
            ->profile()
            ->emailVerification()
            ->emailChangeVerification()
            ->authGuard('customer')
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
            ])

            // Database
            ->databaseTransactions()
            ->databaseNotifications()
            ->lazyLoadedDatabaseNotifications()
            ->databaseNotificationsPolling('30s')

            // Branding
            ->brandName(fn () => $generalSettings->appName)
            ->brandLogo(fn () => $generalSettings->getLogoUrl())
            ->brandLogoHeight('50px')

            // Layout
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->sidebarWidth('300px')
            ->maxContentWidth(Width::Full)
            ->colors(['primary' => Color::Teal])

            // Resources
            ->discoverResources(
                in: app_path('Filament/Customer/Resources'),
                for: 'App\\Filament\\Customer\\Resources',
            )

            // Pages
            ->discoverPages(
                in: app_path('Filament/Customer/Pages'),
                for: 'App\\Filament\\Customer\\Pages',
            )
            ->pages([
                Dashboard::class,
                Pending::class,
                Rejected::class,
                Banned::class,
                Kyc::class,
            ])

            // Widgets
            ->discoverWidgets(
                in: app_path('Filament/Customer/Widgets'),
                for: 'App\\Filament\\Customer\\Widgets',
            )
            ->widgets([
                //
            ])

            // Custom navigation
            ->navigationItems([
                NavigationItem::make('Quick Message')
                    ->url(fn (): string => url('/messaging/messages/quick'))
                    ->icon(Heroicon::OutlinedBolt)
                    ->isActiveWhen(fn (): bool => request()->is('messaging/messages/quick'))
                    ->sort(1),

                NavigationItem::make('Smart Message')
                    ->url(fn (): string => url('/messaging/messages/smart'))
                    ->icon(Heroicon::OutlinedSparkles)
                    ->isActiveWhen(fn (): bool => request()->is('messaging/messages/smart'))
                    ->sort(2),

                NavigationItem::make('API Documentation')
                    ->url(fn (): string => url('/docs'), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->group(CustomerNavigationGroup::Developer)
                    ->visible(fn (): bool => (bool) Filament::auth()->user()?->api_access),
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
                HandleCustomerApproval::class,
                HandleCustomerKyc::class,
            ])
            ->plugins($this->plugins())

            ->renderHook(
                name: PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                hook: fn () => Blade::render("@livewire('balance-button')"),
            )
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
                    'Customer' => 'customer@example.com',
                ]);
        }

        return $plugins;
    }
}
