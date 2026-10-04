<?php

namespace App\Providers;

use App\Notifications\Horizon\LongWaitDetected;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Contracts\LongWaitDetectedNotification;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(LongWaitDetectedNotification::class, LongWaitDetected::class);
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return in_array(optional($user)->email, [
                'bishwajitcadhikary@gmail.com',
                'superman@example.com',
            ]);
        });
    }
}
