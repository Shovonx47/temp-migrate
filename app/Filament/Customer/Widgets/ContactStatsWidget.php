<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ContactStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|array|null $columns = 3;

    public function getStats(): array
    {
        $user = auth()->user();

        $totalContacts = $user->contacts()->count();
        $totalGroups = $user->groups()->count();
        $totalTemplates = $user->templates()->count();

        return [
            Stat::make('Total Contacts', Number::abbreviate($totalContacts))
                ->description('In your lists')
                ->icon('heroicon-o-users'),

            Stat::make('Groups', Number::abbreviate($totalGroups))
                ->description('Contact groups')
                ->color('primary')
                ->icon('heroicon-o-folder'),

            Stat::make('Templates', Number::abbreviate($totalTemplates))
                ->description('Message templates')
                ->color('info')
                ->icon('heroicon-o-document-text'),
        ];
    }
}
