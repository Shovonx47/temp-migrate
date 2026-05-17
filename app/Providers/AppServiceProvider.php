<?php

namespace App\Providers;

use App\Enums\UserType;
use App\Models\Campaign;
use App\Models\Deposit;
use App\Models\Message;
use App\Models\MessageBatch;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use App\Services\Sms\SmsManager;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
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
        $this->authorizeAdminGate();
    }

    private function mapMorphRelations(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'deposit' => Deposit::class,
            'campaign' => Campaign::class,
            'message' => Message::class,
            'message_batch' => MessageBatch::class,
        ]);
    }

    private function authorizeAdminGate(): void
    {
        Gate::before(function (User $user) {
            return $user->type === UserType::Superman ? true : null;
        });
    }
}
