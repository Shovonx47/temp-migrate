<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

final class Register extends BaseRegister
{
    protected Width|string|null $maxContentWidth = Width::SixExtraLarge;

    protected ?bool $hasDatabaseTransactions = true;

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function (): Model {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        $this->sendEmailVerificationNotification($user);

        Filament::auth()->login($user);

        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->columns(1)
            ->components([
                Wizard::make([
                    Wizard\Step::make('Basic Information')
                        ->schema([
                            $this->getNameFormComponent(),
                            $this->getEmailFormComponent(),
                            $this->getPhoneFormComponent(),
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                            $this->getAgreementComponent(),
                        ])
                        ->description('Please provide your basic information.'),
                    Wizard\Step::make('KYC Information')
                        ->columns()
                        ->schema([
                            $this->getNidBackComponent(),
                            $this->getNidFrontComponent(),
                        ])
                        ->description('Please provide your KYC information.'),
                ]),
            ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->prefixIcon(Heroicon::OutlinedUser)
            ->label('Name')
            ->placeholder('Enter your full name')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->prefixIcon(Heroicon::OutlinedEnvelope)
            ->label('Email')
            ->placeholder('Enter your email address')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel())
            ->rule('indisposable');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->prefixIcon(Heroicon::OutlinedLockClosed)
            ->label('Password')
            ->placeholder('Create a secure password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::auth/pages/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->prefixIcon(Heroicon::OutlinedLockClosed)
            ->label('Confirm Password')
            ->placeholder('Re-enter your password')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    protected function getPhoneFormComponent(): Component
    {
        return PhoneInput::make('phone')
            ->label('Phone')
            ->required()
            ->rules(['phone:BD']);
    }

    protected function getAgreementComponent(): Component
    {
        return Checkbox::make('terms')
            ->label(new HtmlString('I agree to the <a href="'.route('terms').'" target="_blank" class="underline hover:text-primary-500">Terms and Conditions</a>'))
            ->required()
            ->dehydrated(false);
    }

    protected function getNidFrontComponent(): Component
    {
        return FileUpload::make('front_nid')
            ->label('Front Side of NID')
            ->image()
            ->required()
            ->maxSize(2048) // 2MB
            ->directory('ids')
            ->visibility('private');
    }

    protected function getNidBackComponent(): Component
    {
        return FileUpload::make('back_nid')
            ->label('Back Side of NID')
            ->image()
            ->required()
            ->maxSize(2048) // 2MB
            ->directory('ids')
            ->visibility('private');
    }
}
