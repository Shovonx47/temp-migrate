<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Deposit;
use App\Models\Message;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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
            'deposit' => Deposit::class,
            'campaign' => Campaign::class,
            'message' => Message::class
        ]);
    }
}
