<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use App\Enums\KycStatus;
use App\Enums\UserStatus;
use App\Filament\Forms\Components\LivePhotoCapture;
use App\Models\User;
use App\Services\Auth\OtpVerificationService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Events\Registered;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class Register extends BaseRegister
{
    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected ?bool $hasDatabaseTransactions = true;

    // Email OTP state
    public string $email_otp = '';

    public bool $email_otp_sent = false;

    public bool $email_verified = false;

    public ?string $email_verification_token = null;

    public int $email_cooldown = 0;

    public ?string $email_error = null;

    public ?string $email_success = null;

    // SMS OTP state
    public string $phone_otp = '';

    public bool $phone_otp_sent = false;

    public bool $phone_verified = false;

    public ?string $phone_verification_token = null;

    public int $phone_cooldown = 0;

    public ?string $phone_error = null;

    public ?string $phone_success = null;

    public function sendEmailOtp(OtpVerificationService $otpService): void
    {
        $this->email_error = null;
        $this->email_success = null;
        $email = $this->data['email'] ?? '';

        if (empty($email)) {
            $this->email_error = 'Please enter your email address first.';

            return;
        }

        $result = $otpService->sendEmailOtp($email);
        if ($result['success']) {
            $this->email_otp_sent = true;
            $this->email_cooldown = (int) ($result['cooldown_seconds'] ?? 60);
            $this->email_success = $result['message'];
            Notification::make()->title($result['message'])->success()->send();
        } else {
            $this->email_error = $result['message'];
            if (! empty($result['cooldown_seconds'])) {
                $this->email_cooldown = (int) $result['cooldown_seconds'];
            }
            Notification::make()->title($result['message'])->danger()->send();
        }
    }

    public function verifyEmailOtp(OtpVerificationService $otpService): void
    {
        $this->email_error = null;
        $this->email_success = null;
        $email = $this->data['email'] ?? '';

        if (empty($this->email_otp)) {
            $this->email_error = 'Please enter the 6-digit verification code.';

            return;
        }

        $result = $otpService->verifyOtp($email, 'email', $this->email_otp);
        if ($result['success']) {
            $this->email_verified = true;
            $this->email_verification_token = $result['token'];
            $this->email_success = 'Email verified successfully!';
            Notification::make()->title('Email verified successfully')->success()->send();
        } else {
            $this->email_error = $result['message'];
            Notification::make()->title($result['message'])->danger()->send();
        }
    }

    public function sendPhoneOtp(OtpVerificationService $otpService): void
    {
        $this->phone_error = null;
        $this->phone_success = null;
        $phone = $this->data['phone'] ?? '';

        if (empty($phone)) {
            $this->phone_error = 'Please enter your mobile number first.';

            return;
        }

        $result = $otpService->sendSmsOtp($phone);
        if ($result['success']) {
            $this->phone_otp_sent = true;
            $this->phone_cooldown = (int) ($result['cooldown_seconds'] ?? 60);
            $this->phone_success = $result['message'];
            Notification::make()->title($result['message'])->success()->send();
        } else {
            $this->phone_error = $result['message'];
            if (! empty($result['cooldown_seconds'])) {
                $this->phone_cooldown = (int) $result['cooldown_seconds'];
            }
            Notification::make()->title($result['message'])->danger()->send();
        }
    }

    public function verifyPhoneOtp(OtpVerificationService $otpService): void
    {
        $this->phone_error = null;
        $this->phone_success = null;
        $phone = $this->data['phone'] ?? '';

        if (empty($this->phone_otp)) {
            $this->phone_error = 'Please enter the 6-digit SMS code.';

            return;
        }

        $result = $otpService->verifyOtp($phone, 'sms', $this->phone_otp);
        if ($result['success']) {
            $this->phone_verified = true;
            $this->phone_verification_token = $result['token'];
            $this->phone_success = 'Mobile verified successfully!';
            Notification::make()->title('Mobile verified successfully')->success()->send();
        } else {
            $this->phone_error = $result['message'];
            Notification::make()->title($result['message'])->danger()->send();
        }
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        try {
            return $this->processRegistration();
        } catch (ValidationException $exception) {
            $this->notifyValidationFailure($exception);

            throw $exception;
        }
    }

    /**
     * Core registration flow. Split out from register() so every
     * validation failure path — required-field, OTP, or duplicate
     * checks — funnels through a single catch that always notifies
     * the user via a toast, in addition to inline field errors.
     */
    protected function processRegistration(): ?RegistrationResponse
    {
        $otpService = app(OtpVerificationService::class);
        $data = $this->form->getState();

        // 1. Strict Backend Email Verification Enforcement
        if (! $otpService->isVerified($data['email'], 'email', $this->email_verification_token)) {
            $this->email_error = 'Email verification is mandatory. Please verify your email with OTP.';
            throw ValidationException::withMessages([
                'data.email' => 'Please verify your email address using the Send OTP button before creating an account.',
            ]);
        }

        // 2. Strict Backend SMS Verification Enforcement
        if (! $otpService->isVerified($data['phone'], 'sms', $this->phone_verification_token)) {
            $this->phone_error = 'Mobile verification is mandatory. Please verify your mobile with OTP.';
            throw ValidationException::withMessages([
                'data.phone' => 'Please verify your mobile number using the Send OTP button before creating an account.',
            ]);
        }

        // 3. Strict Backend Duplicate Checks (Email, Phone, NID Number)
        $normalizedPhone = $otpService->normalizePhoneNumber($data['phone']);
        if (User::where('email', strtolower(trim($data['email'])))->exists()) {
            throw ValidationException::withMessages([
                'data.email' => 'This email address is already registered.',
            ]);
        }

        if ($normalizedPhone && User::where('phone', $normalizedPhone)->exists()) {
            throw ValidationException::withMessages([
                'data.phone' => 'This mobile number is already registered.',
            ]);
        }

        if (! empty($data['nid_number']) && User::where('nid_number', trim($data['nid_number']))->exists()) {
            throw ValidationException::withMessages([
                'data.nid_number' => 'This NID Number is already registered.',
            ]);
        }

        $user = $this->wrapInDatabaseTransaction(function () use ($data, $normalizedPhone): Model {
            $this->callHook('beforeValidate');

            $data = $this->mutateFormDataBeforeRegister($data);
            if ($normalizedPhone) {
                $data['phone'] = $normalizedPhone;
            }

            $this->callHook('afterValidate');
            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        Filament::auth()->login($user);

        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    /**
     * Guarantees the user always sees a clear, unmissable toast whenever
     * registration is blocked for any reason — missing required fields,
     * unverified email/phone, or duplicate email/phone/NID.
     */
    protected function notifyValidationFailure(ValidationException $exception): void
    {
        $messages = collect($exception->errors())
            ->flatten()
            ->unique()
            ->values();

        Notification::make()
            ->title('Registration incomplete')
            ->body(
                $messages->isNotEmpty()
                    ? $messages->take(6)->implode(' ')
                    : 'Please go back, review the form, and fill in every required field before creating your account.'
            )
            ->danger()
            ->persistent()
            ->send();
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        if (is_array($data['front_nid'] ?? null)) {
            $data['front_nid'] = ! empty($data['front_nid']) ? (string) reset($data['front_nid']) : null;
        }
        if (is_array($data['back_nid'] ?? null)) {
            $data['back_nid'] = ! empty($data['back_nid']) ? (string) reset($data['back_nid']) : null;
        }
        if (is_array($data['trade_license_file'] ?? null)) {
            $data['trade_license_file'] = ! empty($data['trade_license_file']) ? (string) reset($data['trade_license_file']) : null;
        }

        // Process Live Camera Photo Base64 Stream
        if (filled($data['live_photo'] ?? null) && str_starts_with((string) $data['live_photo'], 'data:image')) {
            $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', (string) $data['live_photo']);
            $decoded = base64_decode($base64Image);
            $filename = 'live_photos/live_'.Str::uuid().'.jpg';
            Storage::disk('private')->put($filename, $decoded);
            $data['live_photo'] = $filename;
        }

        $hasTradeLicense = filled($data['trade_license_number'] ?? null) || filled($data['trade_license_file'] ?? null);

        $data['status'] = UserStatus::Pending;
        $data['kyc_status'] = KycStatus::UnderReview;
        $data['email_verified_at'] = now();
        $data['phone_verified_at'] = now();
        $data['kyc_submitted_at'] = now();
        $data['trade_license_submitted'] = $hasTradeLicense;

        if (! $hasTradeLicense) {
            $data['trade_license_number'] = null;
            $data['trade_license_file'] = null;
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. Personal Information Section
                Section::make('Personal Information')
                    ->description('Provide your personal details and verify your email and mobile number.')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->validationAttribute('Full Name')
                            ->placeholder('Enter your full name')
                            ->prefixIcon(Heroicon::OutlinedUser)
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('email')
                                ->label('Email Address')
                                ->validationAttribute('Email Address')
                                ->email()
                                ->required()
                                ->view('filament.forms.components.inline-email-verification')
                                ->columnSpan(1),

                            TextInput::make('phone')
                                ->label('Mobile Number')
                                ->validationAttribute('Mobile Number')
                                ->required()
                                ->view('filament.forms.components.inline-phone-verification')
                                ->columnSpan(1),
                        ]),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('password')
                                ->label('Password')
                                ->placeholder('Create a strong password')
                                ->prefixIcon(Heroicon::OutlinedLockClosed)
                                ->password()
                                ->revealable(filament()->arePasswordsRevealable())
                                ->required()
                                ->rule(Password::default())
                                ->showAllValidationMessages()
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->same('passwordConfirmation')
                                ->validationAttribute(__('filament-panels::auth/pages/register.form.password.validation_attribute')),

                            TextInput::make('passwordConfirmation')
                                ->label('Confirm Password')
                                ->placeholder('Re-enter your password')
                                ->prefixIcon(Heroicon::OutlinedLockClosed)
                                ->password()
                                ->revealable(filament()->arePasswordsRevealable())
                                ->required()
                                ->dehydrated(false),
                        ]),
                    ]),

                // 2. NID / KYC Verification Section
                Section::make('NID / KYC Verification')
                    ->description('Provide your National ID number and official document photos for KYC review.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->schema([
                        TextInput::make('nid_number')
                            ->label('NID Number')
                            ->validationAttribute('NID Number')
                            ->placeholder('Enter your 10, 13, or 17-digit NID Number')
                            ->prefixIcon(Heroicon::OutlinedCreditCard)
                            ->required()
                            ->unique(table: 'users', column: 'nid_number')
                            ->maxLength(30),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            FileUpload::make('front_nid')
                                ->label('NID Front Side')
                                ->validationAttribute('NID Front Side')
                                ->image()
                                ->required()
                                ->maxSize(4096) // 4MB
                                ->directory('ids')
                                ->visibility('private')
                                ->helperText('Clear color photo of the front side of your NID card.'),

                            FileUpload::make('back_nid')
                                ->label('NID Back Side')
                                ->validationAttribute('NID Back Side')
                                ->image()
                                ->required()
                                ->maxSize(4096) // 4MB
                                ->directory('ids')
                                ->visibility('private')
                                ->helperText('Clear color photo of the back side of your NID card.'),
                        ]),
                    ]),

                // 3. Live Camera Photo Section
                Section::make('Live Photo (Camera Capture Only)')
                    ->description('Live selfie photo captured via your device camera. Gallery upload is restricted.')
                    ->icon(Heroicon::OutlinedCamera)
                    ->schema([
                        LivePhotoCapture::make('live_photo')
                            ->label('Live Camera Photo')
                            ->validationAttribute('Live Camera Photo')
                            ->required(),
                    ]),

                // 4. Business Information & Trade License (Optional)
                Section::make('Business Information (Optional)')
                    ->description('Trade License is optional. You can create your account and submit trade license details anytime.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('business_name')
                                ->label('Business / Company Name')
                                ->placeholder('Optional business name')
                                ->prefixIcon(Heroicon::OutlinedBuildingOffice)
                                ->maxLength(255),

                            TextInput::make('trade_license_number')
                                ->label('Trade License Number')
                                ->placeholder('Optional trade license number')
                                ->prefixIcon(Heroicon::OutlinedDocumentText)
                                ->maxLength(100),
                        ]),

                        Textarea::make('business_address')
                            ->label('Business Address')
                            ->placeholder('Optional business address')
                            ->rows(2)
                            ->maxLength(500),

                        FileUpload::make('trade_license_file')
                            ->label('Trade License Document')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->directory('trade_licenses')
                            ->visibility('private')
                            ->helperText('Optional. PDF or image of your Trade License document.'),
                    ]),

                // 5. Terms and Agreement
                Section::make()
                    ->schema([
                        Checkbox::make('terms')
                            ->label(new HtmlString('I agree to the <a href="'.route('terms').'" target="_blank" class="underline text-teal-600 hover:text-teal-700 dark:text-teal-400">Terms & Conditions</a> and <a href="'.route('terms').'" target="_blank" class="underline text-teal-600 hover:text-teal-700 dark:text-teal-400">Privacy Policy</a>'))
                            ->validationAttribute('Terms & Conditions')
                            ->required()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
