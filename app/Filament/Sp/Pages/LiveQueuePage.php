<?php

namespace App\Filament\Sp\Pages;

use App\Filament\Sp\Widgets\SpLiveMessageStreamWidget;
use App\Filament\Sp\Widgets\SpLiveQueueStatsWidget;
use App\Services\SpPermissionService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class LiveQueuePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Live Queue';

    protected static ?string $title = 'Customers Live Queue';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'live-queue';

    protected string $view = 'filament.sp.pages.live-queue';

    public function getWidgets(): array
    {
        return [
            SpLiveQueueStatsWidget::class,
            SpLiveMessageStreamWidget::class,
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth('sp')->user();
        if (! $user) {
            return false;
        }

        // SP Owner has full access; employee must have customer viewing permission
        return $user->sp_id === null
            || app(SpPermissionService::class)->can($user, 'customers.view');
    }
}
