<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages\Account;

use Filament\Actions\Action;
use Filament\Pages\Concerns\HasRoutes;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;

abstract class Page extends SimplePage
{
    use HasRoutes;

    final public static function registerNavigationItems(): false
    {
        return false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->alignBetween()
            ->components([
                $this->getLogoutAction(),
            ]);
    }

    protected function getLogoutAction(): Actions
    {
        return Actions::make([
            Action::make('logout')
                ->label(__('Logout'))
                ->button()
                ->outlined()
                ->color('secondary')
                ->postToUrl()
                ->url(route('filament.customer.auth.logout'))
                ->extraAttributes([
                    'style' => 'width: 100%',
                ]),
        ])
            ->fullWidth();
    }
}
