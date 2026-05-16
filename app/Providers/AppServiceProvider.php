<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Deposit;
use App\Models\Message;
use App\Models\User;
use App\Services\Payments\PaymentManager;
use App\Services\Sms\SmsManager;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class, fn ($app) => new PaymentManager($app));

        $this->app->singleton(SmsManager::class, fn ($app) => new SmsManager($app));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->mapMorphRelations();
        $this->registerPhoneInputTagsMacro();
    }

    /**
     * Turn a PhoneInput into a multi-value tags input. State becomes string[] (E.164),
     * rendered as chips. Replaces the parent's single-string state hooks so the
     * underlying libphonenumber formatter never receives an array.
     */
    private function registerPhoneInputTagsMacro(): void
    {
        PhoneInput::macro('tags', function () {
            /** @var PhoneInput $this */
            return $this
                ->view('filament.forms.components.phone-tags-input')
                ->default([])
                ->afterStateHydrated(function (PhoneInput $component, $state): void {
                    if (is_array($state)) {
                        return;
                    }
                    $component->state(filled($state) ? [(string) $state] : []);
                })
                ->mutateDehydratedStateUsing(fn ($state): array => array_values(array_filter(
                    array_map('trim', (array) ($state ?? [])),
                    fn (string $value): bool => $value !== '',
                )));
        });
    }

    private function mapMorphRelations(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'deposit' => Deposit::class,
            'campaign' => Campaign::class,
            'message' => Message::class,
        ]);
    }
}
