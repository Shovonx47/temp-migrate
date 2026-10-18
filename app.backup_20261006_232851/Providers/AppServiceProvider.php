<?php

namespace App\Providers;

use App\Enums\UserType;
use App\Jobs\SendBatchBulkSmsJob;
use App\Jobs\SendBatchSingleSmsJob;
use App\Models\AccessToken;
use App\Models\Batch;
use App\Models\Campaign;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\Message;
use App\Models\SpDistribution;
use App\Models\User;
use App\Services\Payment\PaymentManager;
use App\Services\Sms\SmsManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
        $this->configureRateLimiters();
        $this->configureSanctum();
    }

    private function mapMorphRelations(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'deposit' => Deposit::class,
            'campaign' => Campaign::class,
            'message' => Message::class,
            'message_batch' => Batch::class,
            'sp_distribution' => SpDistribution::class,
        ]);
    }

    private function authorizeAdminGate(): void
    {
        Gate::before(function (User $user) {
            return $user->type === UserType::Superman ? true : null;
        });
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('sms', function (object $job) {
            /** @var ?Gateway $gateway */
            $gateway = match (true) {
                method_exists($job, 'getGateway') => $job->getGateway(),
                $job instanceof SendBatchBulkSmsJob,
                $job instanceof SendBatchSingleSmsJob => $job->batch->sender?->gateway,
                default => null,
            };

            return $gateway
                ? Limit::perSecond(max(1, (int) $gateway->requests_per_second))
                    ->by('sms-gateway:'.$gateway->id)
                : Limit::none();
        });
    }

    private function configureSanctum(): void
    {
        Sanctum::getAccessTokenFromRequestUsing(function ($request) {
            return $request->bearerToken()
                ?? $request->input('token')
                ?? $request->query('token');
        });

        Sanctum::usePersonalAccessTokenModel(AccessToken::class);
    }
}
