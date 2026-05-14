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
