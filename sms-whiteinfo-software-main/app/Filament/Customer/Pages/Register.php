<?php

declare(strict_types=1);

namespace App\Filament\Customer\Pages;

use App\Enums\KycStatus;
use App\Enums\TariffPlanAssignmentStatus;
use App\Enums\UserStatus;
use App\Filament\Forms\Components\LivePhotoCapture;
use App\Models\TariffPlan;
use App\Models\TariffPlanAssignment;
use App\Models\User;
use App\Services\Auth\OtpVerificationService;
use App\Services\TariffSlugService;
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
use Livewire\Attributes\Locked;

final class Register extends BaseRegister
{
    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    protected ?bool $hasDatabaseTransactions = true;

    // Signup-link state. #[Locked] means the browser can never change these values;
    // they are only ever set here, on the server, from a re-validated session slug.
    #[Locked]
    public ?string $signupPlanSlug = null;

    #[Locked]
    public ?string $signupPlanName = null; // display only

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

    public function mount(): void
    {
        parent::mount();

        $slug = session()->get(TariffSlugService::SESSION_KEY);
        $result = is_string($slug) ? TariffSlugService::resolve($slug) : null;

        if ($result === null) {
            // Nothing pending, or the link stopped being valid: plain registration.
            session()->forget(TariffSlugService::SESSION_KEY);

            return;
        }

        $this->signupPlanSlug = $result['canonical_slug'];
        $this->signupPlanName = $result['plan']->name;
    }

    /**
     * Re-resolve the plan from the locked slug. Never reads form data, query string, or request body.
     */
    protected function resolveSignupPlan(): ?TariffPlan
    {
        if (blank($this->signupPlanSlug)) {
            return null;
        }

        return TariffSlugService::resolve($this->signupPlanSlug)['plan'] ?? null;
    }

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

        // Signup link: confirm the plan is still valid before doing anything (some steps below
        // are irreversible). If it was deactivated after the page loaded, no account is created.
        $signupPlan = null;

        if (filled($this->signupPlanSlug)) {
            $signupPlan = $this->resolveSignupPlan();

            if ($signupPlan === null) {
                Notification::make()
                    ->title('This signup link is no longer active.')
                    ->danger()
                    ->persistent()
                    ->send();

                return null;
            }
        }

        // Validate all Filament form fields first.
        // On failure, scroll to the first failing field in sequential form order, then re-throw.
        try {
            $data = $this->form->getState();
        } catch (ValidationException $e) {
            $this->dispatchScrollForFirstError($e->errors());

            throw $e;
        }

        // 1. Strict Backend Email Verification Enforcement
        // Scroll to the email OTP area when the field is filled but OTP not yet verified.
        if (! $otpService->isVerified($data['email'], 'email', $this->email_verification_token)) {
            $this->email_error = 'Email verification is mandatory. Please verify your email with OTP.';
            $this->dispatch('scroll-to-first-error', fieldId: 'field-wrap-email');
            throw ValidationException::withMessages([
                'data.email' => 'Please verify your email address using the Send OTP button before creating an account.',
            ]);
        }

        // 2. Strict Backend SMS Verification Enforcement
        // Scroll to the phone OTP area when the field is filled but OTP not yet verified.
        if (! $otpService->isVerified($data['phone'], 'sms', $this->phone_verification_token)) {
            $this->phone_error = 'Mobile verification is mandatory. Please verify your mobile with OTP.';
            $this->dispatch('scroll-to-first-error', fieldId: 'field-wrap-phone');
            throw ValidationException::withMessages([
                'data.phone' => 'Please verify your mobile number using the Send OTP button before creating an account.',
            ]);
        }

        // 3. Strict Backend Duplicate Checks (Email, Phone, NID Number)
        // Use withoutGlobalScopes() to bypass SoftDeletes + ScopedBySp for accurate checks.
        $normalizedPhone = $otpService->normalizePhoneNumber($data['phone']);
        $normalizedEmail = strtolower(trim($data['email']));
        $normalizedNid = trim($data['nid_number'] ?? '');

        // Check active (non-soft-deleted) users first — these are real duplicates.
        if (User::withoutGlobalScopes()->whereNull('deleted_at')->where('email', $normalizedEmail)->exists()) {
            $this->dispatch('scroll-to-first-error', fieldId: 'field-wrap-email');
            throw ValidationException::withMessages([
                'data.email' => 'This email address is already registered.',
            ]);
        }

        if ($normalizedPhone && User::withoutGlobalScopes()->whereNull('deleted_at')->where('phone', $normalizedPhone)->exists()) {
            $this->dispatch('scroll-to-first-error', fieldId: 'field-wrap-phone');
            throw ValidationException::withMessages([
                'data.phone' => 'This mobile number is already registered.',
            ]);
        }

        if ($normalizedNid !== '' && User::withoutGlobalScopes()->whereNull('deleted_at')->where('nid_number', $normalizedNid)->exists()) {
            $this->dispatch('scroll-to-first-error', fieldId: 'data.nid_number');
            throw ValidationException::withMessages([
                'data.nid_number' => 'This NID Number is already registered.',
            ]);
        }

        // Purge soft-deleted users that would clash with DB unique constraints.
        // These records were intentionally deleted by an admin, so force-deleting
        // them is safe and allows the same person (or credentials) to re-register.
        User::withoutGlobalScopes()->onlyTrashed()
            ->where(function ($query) use ($normalizedEmail, $normalizedPhone, $normalizedNid) {
                $query->where('email', $normalizedEmail);
                if ($normalizedPhone) {
                    $query->orWhere('phone', $normalizedPhone);
                }
                if ($normalizedNid !== '') {
                    $query->orWhere('nid_number', $normalizedNid);
                }
            })
            ->each(function (User $trashedUser): void {
                $trashedUser->forceDelete();
            });

        $user = $this->wrapInDatabaseTransaction(function () use ($data, $normalizedPhone, $signupPlan): Model {
            $this->callHook('beforeValidate');

            $data = $this->mutateFormDataBeforeRegister($data);
            if ($normalizedPhone) {
                $data['phone'] = $normalizedPhone;
            }

            // Signup link on a reseller plan: the customer is placed under that reseller.
            // The reseller comes ONLY from the plan's owner, never from form or request data.
            if ($signupPlan?->sp_id) {
                $data['sp_id'] = $signupPlan->sp_id;
            }

            $this->callHook('afterValidate');
            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            // Same transaction as the account: if this fails, the account is rolled back too.
            if ($signupPlan) {
                TariffPlanAssignment::create([
                    'user_id' => $user->id,
                    'tariff_plan_id' => $signupPlan->id,
                    'assigned_by' => null,
                    'effective_from' => now()->toDateString(),
                    'status' => TariffPlanAssignmentStatus::Active->value,
                    'notes' => 'Auto-assigned via signup link: /register/'.$signupPlan->slug,
                ]);
            }

            $this->callHook('afterRegister');

            return $user;
        });

        event(new Registered($user));

        Filament::auth()->login($user);

        session()->forget(TariffSlugService::SESSION_KEY);
        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    /**
     * Guarantees the user always sees a clear, unmissable toast whenever
     * registration is blocked for any reason — missing required fields,
     * unverified email/phone, or duplicate email/phone/NID.
     * Shows the error message for the FIRST failing field in sequential form order.
     */
    protected function notifyValidationFailure(ValidationException $exception): void
    {
        $errors = $exception->errors();

        // Walk fields in the exact order they appear in the form.
        // The first field with an error determines the notification body.
        $fieldOrder = [
            'data.name',
            'data.email',
            'data.phone',
            'data.password',
            'data.passwordConfirmation',
            'data.nid_number',
            'data.front_nid',
            'data.back_nid',
            'data.live_photo',
            'data.business_name',
            'data.trade_license_number',
            'data.business_address',
            'data.trade_license_file',
            'data.terms',
        ];

        $firstMessage = null;
        foreach ($fieldOrder as $key) {
            if (! empty($errors[$key])) {
                $msgs = $errors[$key];
                $firstMessage = is_array($msgs) ? $msgs[0] : (string) $msgs;
                break;
            }
        }

        Notification::make()
            ->title('Registration incomplete')
            ->body($firstMessage ?? 'Please go back, review the form, and fill in every required field before creating your account.')
            ->danger()
            ->persistent()
            ->send();
    }

    /**
     * Dispatches a browser event that the page JS listener uses to smooth-scroll
     * to the first failing field in sequential form order.
     *
     * Custom blade wrappers expose an explicit HTML id (e.g. "field-wrap-email").
     * Standard Filament inputs use their statePath as the HTML id (e.g. "data.name").
     * Section containers are used for FileUpload fields (e.g. "section-nid-kyc").
     */
    protected function dispatchScrollForFirstError(array $errors): void
    {
        // Map: Filament error key → HTML element id to scroll to, in exact form order.
        $fieldScrollMap = [
            'data.name' => 'data.name',
            'data.email' => 'field-wrap-email',
            'data.phone' => 'field-wrap-phone',
            'data.password' => 'data.password',
            'data.passwordConfirmation' => 'data.passwordConfirmation',
            'data.nid_number' => 'data.nid_number',
            'data.front_nid' => 'section-nid-kyc',
            'data.back_nid' => 'section-nid-kyc',
            'data.live_photo' => 'field-wrap-live-photo',
            'data.business_name' => 'data.business_name',
            'data.trade_license_number' => 'data.trade_license_number',
            'data.business_address' => 'data.business_address',
            'data.trade_license_file' => 'section-business',
            'data.terms' => 'data.terms',
        ];

        foreach ($fieldScrollMap as $errorKey => $scrollId) {
            if (! empty($errors[$errorKey])) {
                $this->dispatch('scroll-to-first-error', fieldId: $scrollId);

                return;
            }
        }
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

        // Trade license is now mandatory — always mark as submitted.
        $data['status'] = UserStatus::Pending;
        $data['kyc_status'] = KycStatus::UnderReview;
        $data['email_verified_at'] = now();
        $data['phone_verified_at'] = now();
        $data['kyc_submitted_at'] = now();
        $data['trade_license_submitted'] = true;

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. Personal Information Section
                Section::make('Personal Information')
                    ->description(fn (): string => filled($this->signupPlanName)
                        ? 'You are signing up for the "'.$this->signupPlanName.'" plan. Provide your personal details and verify your email and mobile number.'
                        : 'Provide your personal details and verify your email and mobile number.')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->validationAttribute('Full Name')
                            ->validationMessages([
                                'required' => 'Your full name is required. Please enter your first and last name to proceed.',
                            ])
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
                                ->validationMessages([
                                    'required' => 'An email address is required. Your OTP verification code will be sent to this email.',
                                    'email' => 'Please enter a valid email address (e.g. you@example.com).',
                                ])
                                ->email()
                                ->required()
                                ->view('filament.forms.components.inline-email-verification')
                                ->columnSpan(1),

                            TextInput::make('phone')
                                ->label('Mobile Number')
                                ->validationAttribute('Mobile Number')
                                ->validationMessages([
                                    'required' => 'A mobile number is required. Your SMS OTP verification code will be sent to this number.',
                                ])
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
                                ->validationMessages([
                                    'required' => 'A password is required to secure your account. Please create a strong password.',
                                    'same' => 'The password and confirmation password do not match. Please re-enter them.',
                                ])
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
                                ->validationMessages([
                                    'required' => 'Please re-enter your password in this field to confirm they match.',
                                ])
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
                    ->extraAttributes(['id' => 'section-nid-kyc'])
                    ->schema([
                        TextInput::make('nid_number')
                            ->label('NID Number')
                            ->validationAttribute('NID Number')
                            ->validationMessages([
                                'required' => 'Your National ID (NID) number is required for KYC identity verification. Please enter your 10, 13, or 17-digit NID number.',
                                'unique' => 'This NID number is already registered to another account.',
                            ])
                            ->placeholder('Enter your 10, 13, or 17-digit NID Number')
                            ->prefixIcon(Heroicon::OutlinedCreditCard)
                            ->required()
                            ->unique(
                                table: 'users',
                                column: 'nid_number',
                                modifyRuleUsing: fn ($rule) => $rule->withoutTrashed(),
                            )
                            ->maxLength(30),

                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            FileUpload::make('front_nid')
                                ->label('NID Front Side')
                                ->validationAttribute('NID Front Side')
                                ->validationMessages([
                                    'required' => 'Please upload a clear photo of the FRONT side of your NID card. This is required for KYC verification.',
                                ])
                                ->image()
                                ->required()
                                ->maxSize(4096) // 4MB
                                ->directory('ids')
                                ->visibility('private')
                                ->helperText('Clear color photo of the front side of your NID card.'),

                            FileUpload::make('back_nid')
                                ->label('NID Back Side')
                                ->validationAttribute('NID Back Side')
                                ->validationMessages([
                                    'required' => 'Please upload a clear photo of the BACK side of your NID card. Both sides are required for KYC verification.',
                                ])
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
                            ->validationMessages([
                                'required' => 'A live selfie photo is required for identity verification. Please click "Open Webcam" or "Use Phone Camera" to capture your photo.',
                            ])
                            ->required(),
                    ]),

                // 4. Business Information & Trade License
                Section::make('Business Information')
                    ->description('Provide your business details and trade license. All fields in this section are required.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->collapsible()
                    ->collapsed(false)
                    ->extraAttributes(['id' => 'section-business'])
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('business_name')
                                ->label('Business / Company Name')
                                ->validationAttribute('Business / Company Name')
                                ->validationMessages([
                                    'required' => 'Your business or company name is required. Please enter the registered name of your business.',
                                ])
                                ->placeholder('Enter your business or company name')
                                ->prefixIcon(Heroicon::OutlinedBuildingOffice)
                                ->required()
                                ->maxLength(255),

                            TextInput::make('trade_license_number')
                                ->label('Trade License Number')
                                ->validationAttribute('Trade License Number')
                                ->validationMessages([
                                    'required' => 'Your trade license number is required. Please enter the official number printed on your trade license document.',
                                ])
                                ->placeholder('Enter your trade license number')
                                ->prefixIcon(Heroicon::OutlinedDocumentText)
                                ->required()
                                ->maxLength(100),
                        ]),

                        Textarea::make('business_address')
                            ->label('Business Address')
                            ->validationAttribute('Business Address')
                            ->validationMessages([
                                'required' => 'Your business address is required. Please enter the full address of your business premises.',
                            ])
                            ->placeholder('Enter your business address')
                            ->rows(2)
                            ->required()
                            ->maxLength(500),

                        FileUpload::make('trade_license_file')
                            ->label('Trade License Document')
                            ->validationAttribute('Trade License Document')
                            ->validationMessages([
                                'required' => 'Please upload your Trade License document. Accepted formats are PDF or image (JPG, PNG). Max size 5MB.',
                            ])
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->required()
                            ->directory('trade_licenses')
                            ->visibility('private')
                            ->helperText('PDF or image of your Trade License document.'),
                    ]),

                // 5. Terms and Agreement
                Section::make()
                    ->schema([
                        Checkbox::make('terms')
                            ->label(new HtmlString('I agree to the <a href="'.route('terms').'" target="_blank" class="underline text-teal-600 hover:text-teal-700 dark:text-teal-400">Terms &amp; Conditions</a> and <a href="'.route('terms').'" target="_blank" class="underline text-teal-600 hover:text-teal-700 dark:text-teal-400">Privacy Policy</a>'))
                            ->validationAttribute('Terms & Conditions')
                            ->validationMessages([
                                'required' => 'You must read and agree to the Terms & Conditions and Privacy Policy before creating your account.',
                                'accepted' => 'You must read and agree to the Terms & Conditions and Privacy Policy before creating your account.',
                            ])
                            ->required()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}
