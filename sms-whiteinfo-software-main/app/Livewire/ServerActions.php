<?php

namespace App\Livewire;

use App\Jobs\OptimizeApplicationJob;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Process;
use Livewire\Component;

class ServerActions extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public function render(): View
    {
        return view('livewire.server-actions');
    }

    public function restartHorizonAction(): Action
    {
        return Action::make('restartHorizon')
            ->label('Restart Horizon')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Restart Horizon?')
            ->modalDescription('This will gracefully terminate all Horizon workers and Supervisor will restart them automatically. Any jobs currently being processed will finish first, but the dashboard will briefly go offline. Continue?')
            ->modalSubmitActionLabel('Yes, restart')
            ->action(function () {
                Process::run('php artisan horizon:terminate');

                Notification::make()
                    ->title('Horizon is restarting')
                    ->body('Workers will be back online in a few seconds. Refresh the Horizon dashboard if it appears unavailable.')
                    ->success()
                    ->send();
            });
    }

    public function optimizeApplicationAction(): Action
    {
        return Action::make('optimizeApplication')
            ->label('Optimize App')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Optimize application?')
            ->modalDescription('This will clear and rebuild all framework and Filament caches (config, routes, views, events, and components). The first few requests after optimization may be slightly slower while caches warm up. Continue?')
            ->modalSubmitActionLabel('Yes, optimize')
            ->action(function () {
                OptimizeApplicationJob::dispatch(Filament::auth()->user());

                Notification::make()
                    ->title('Optimization queued')
                    ->body("We'll notify you here once it's done. This usually takes under a minute.")
                    ->success()
                    ->send();
            });
    }

    public function actionsGroup(): ActionGroup
    {
        return ActionGroup::make([
            $this->restartHorizonAction(),
            $this->optimizeApplicationAction(),
        ])
            ->label('Server')
            ->size('md')
            ->color('gray')
            ->hiddenLabel(false)
            ->icon(Heroicon::OutlinedServer);
    }
}
