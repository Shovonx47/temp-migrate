<?php

namespace App\Jobs;

use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class OptimizeApplicationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(protected User $user) {}

    public function handle(): void
    {
        $commands = [
            'optimize:clear',
            'filament:optimize-clear',
            'optimize',
            'filament:optimize',
        ];

        foreach ($commands as $command) {
            Artisan::call($command);
        }

        Notification::make()
            ->title('Application optimized')
            ->body('All caches have been cleared and rebuilt successfully.')
            ->success()
            ->sendToDatabase($this->user);
    }

    public function failed(Throwable $exception): void
    {
        Notification::make()
            ->title('Optimization failed')
            ->body('Something went wrong: '.$exception->getMessage())
            ->danger()
            ->sendToDatabase($this->user);
    }
}
