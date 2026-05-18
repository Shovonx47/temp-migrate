<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages\Account;

use App\Filament\Customer\Pages\Dashboard;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;

final class Kyc extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public static function getSlug(?Panel $panel = null): string
    {
        return 'account/kyc';
    }

    public function getTitle(): string
    {
        return 'KYC Verification Required';
    }

    public function getHeading(): string
    {
        return 'Your account requires KYC verification';
    }

    public function getSubheading(): string
    {
        return 'We require KYC verification to ensure the security of our platform. Please submit the necessary documents to complete your account verification.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('front_nid')
                    ->label('Front Side of NID')
                    ->image()
                    ->required()
                    ->maxSize(2048)
                    ->directory('ids')
                    ->visibility('private'),
                FileUpload::make('back_nid')
                    ->label('Back Side of NID')
                    ->image()
                    ->required()
                    ->maxSize(2048)
                    ->directory('ids')
                    ->visibility('private'),
                Actions::make([
                    Action::make('submit')
                        ->label('Submit KYC')
                        ->action('submit')
                        ->extraAttributes(['style' => 'width: 100%']),
                ])
                    ->fullWidth(),
                $this->getLogoutAction(),
            ]);
    }

    public function submit(): void
    {
        $data = $this->content->getState();

        Filament::auth()->user()->update($data);

        Notification::make()
            ->title('KYC submitted successfully')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl(panel: 'customer'));
    }
}
